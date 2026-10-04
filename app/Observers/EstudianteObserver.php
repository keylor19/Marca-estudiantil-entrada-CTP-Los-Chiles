<?php

namespace App\Observers;

use App\Models\Estudiante;

class EstudianteObserver
{
    /**
     * Un estudiante nuevo queda matriculado en el año en curso si no se indicó otro.
     */
    public function creating(Estudiante $estudiante): void
    {
        $estudiante->anio_matricula ??= Estudiante::cursoLectivo();
    }

    /**
     * Genera el código de barras único (EST000001, EST000002, ...) a partir del id,
     * lo que garantiza que nunca se repita aunque se creen estudiantes al mismo tiempo.
     */
    public function created(Estudiante $estudiante): void
    {
        if (empty($estudiante->codigo_barra)) {
            $estudiante->codigo_barra = static::codigoPara($estudiante->id);
            $estudiante->saveQuietly();
        }
    }

    public static function codigoPara(int $id): string
    {
        return 'EST'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
