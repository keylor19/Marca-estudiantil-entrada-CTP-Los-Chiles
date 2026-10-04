<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subgrupo extends Model
{
    use HasFactory;

    protected $fillable = ['seccion_id', 'nombre', 'especialidad', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class);
    }

    /**
     * Nombre para mostrar, ej. "10-2 A".
     */
    public function nombreCompleto(): string
    {
        return trim("{$this->seccion->nombre} {$this->nombre}");
    }
}
