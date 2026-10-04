<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

/**
 * Guarda la columna `fecha` siempre como "Y-m-d" (sin hora) en cualquier motor de base de datos,
 * para que las consultas por fecha exacta funcionen igual en MySQL y en SQLite.
 */
trait TieneFechaSinHora
{
    protected function fecha(): Attribute
    {
        return Attribute::make(
            get: fn (?string $valor) => $valor ? Carbon::parse($valor)->startOfDay() : null,
            set: fn (mixed $valor) => $valor ? Carbon::parse($valor)->toDateString() : null,
        );
    }
}
