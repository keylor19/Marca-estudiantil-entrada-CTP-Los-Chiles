<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    use HasFactory;

    protected $fillable = [
        'cedula', 'nombre', 'apellido1', 'apellido2', 'fecha_nacimiento',
        'subgrupo_id', 'foto', 'codigo_barra', 'activo', 'anio_matricula',
        'encargado_nombre', 'encargado_parentesco', 'encargado_telefono', 'encargado_correo', 'avisar_encargado',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'anio_matricula' => 'integer',
            'fecha_nacimiento' => 'date',
            'avisar_encargado' => 'boolean',
        ];
    }

    public function avisos(): HasMany
    {
        return $this->hasMany(Aviso::class);
    }

    /**
     * Teléfono del encargado en formato internacional sin "+" (ej. 50688887777), como lo pide WhatsApp.
     * Los números de 8 dígitos se toman como de Costa Rica.
     */
    public function telefonoWhatsApp(): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $this->encargado_telefono);

        return match (true) {
            strlen($digitos) === 8 => '506'.$digitos,
            strlen($digitos) >= 10 => $digitos,
            default => null,
        };
    }

    public function subgrupo(): BelongsTo
    {
        return $this->belongsTo(Subgrupo::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->apellido1} {$this->apellido2}");
    }

    /**
     * Sección para mostrar, ej. "10-2 A".
     */
    public function seccionNombre(): string
    {
        return $this->subgrupo?->nombreCompleto() ?? '';
    }

    public function fotoUrl(): string
    {
        return route('estudiantes.foto', ['estudiante' => $this->id, 'v' => $this->updated_at?->timestamp]);
    }

    /**
     * Año lectivo en curso (en Costa Rica coincide con el año calendario).
     */
    public static function cursoLectivo(): int
    {
        return now()->year;
    }

    /**
     * Matriculado en el año en curso (o ya en el siguiente, si la matrícula se adelantó).
     */
    public function matriculaVigente(): bool
    {
        return $this->anio_matricula !== null && $this->anio_matricula >= self::cursoLectivo();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeMatriculaVigente(Builder $query): Builder
    {
        return $query->where('anio_matricula', '>=', self::cursoLectivo());
    }

    /**
     * Estudiantes que no aparecen en la matrícula del año en curso.
     */
    public function scopeMatriculaVencida(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('anio_matricula')->orWhere('anio_matricula', '<', self::cursoLectivo()));
    }

    public function scopeDeSeccion(Builder $query, int $seccionId): Builder
    {
        return $query->whereHas('subgrupo', fn (Builder $subgrupo) => $subgrupo->where('seccion_id', $seccionId));
    }

    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        if (blank($texto)) {
            return $query;
        }

        foreach (preg_split('/\s+/', trim($texto)) as $palabra) {
            $query->where(function (Builder $query) use ($palabra) {
                $query->where('cedula', 'like', "%{$palabra}%")
                    ->orWhere('codigo_barra', $palabra)
                    ->orWhere('nombre', 'like', "%{$palabra}%")
                    ->orWhere('apellido1', 'like', "%{$palabra}%")
                    ->orWhere('apellido2', 'like', "%{$palabra}%");
            });
        }

        return $query;
    }

    public function scopeOrdenAlfabetico(Builder $query): Builder
    {
        return $query->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombre');
    }
}
