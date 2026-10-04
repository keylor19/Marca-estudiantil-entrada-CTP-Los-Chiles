<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use App\Models\Concerns\TieneFechaSinHora;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use HasFactory, TieneFechaSinHora;

    protected $fillable = [
        'estudiante_id', 'fecha', 'hora_entrada', 'hora_salida', 'estado',
        'usuario_id', 'usuario_salida_id', 'observacion',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoAsistencia::class,
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usuarioSalida(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_salida_id');
    }

    /**
     * Hora en formato 12 h, ej. "07:12:35 a. m.".
     */
    public static function formatoHora(?string $hora): string
    {
        return $hora ? date('h:i:s A', strtotime($hora)) : '--';
    }
}
