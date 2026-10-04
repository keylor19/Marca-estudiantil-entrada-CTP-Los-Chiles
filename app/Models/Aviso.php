<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aviso extends Model
{
    public const ENVIADO = 'enviado';

    public const FALLIDO = 'fallido';

    protected $fillable = ['estudiante_id', 'asistencia_id', 'tipo', 'canal', 'destino', 'estado', 'error'];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }
}
