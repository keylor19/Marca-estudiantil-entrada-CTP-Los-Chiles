<?php

namespace App\Services;

use App\Models\Estudiante;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FotoEstudiante
{
    private const ANCHO_MAXIMO = 600;

    /**
     * Reduce la foto a un tamaño adecuado para el carnet, la vuelve a codificar como JPG
     * (lo que elimina cualquier contenido extraño del archivo) y la guarda en el disco privado.
     */
    public function guardar(Estudiante $estudiante, string $contenido): void
    {
        $original = @imagecreatefromstring($contenido);

        if ($original === false) {
            throw new InvalidArgumentException('El archivo no es una imagen válida.');
        }

        $ancho = imagesx($original);
        $alto = imagesy($original);
        $escala = min(1, self::ANCHO_MAXIMO / $ancho);
        $imagen = imagescale($original, (int) round($ancho * $escala), (int) round($alto * $escala));

        ob_start();
        imagejpeg($imagen, null, 85);
        $jpg = ob_get_clean();

        $ruta = 'fotos/'.$estudiante->id.'-'.Str::random(8).'.jpg';
        Storage::disk('local')->put($ruta, $jpg);

        $this->eliminar($estudiante);
        $estudiante->update(['foto' => $ruta]);
    }

    public function eliminar(Estudiante $estudiante): void
    {
        if ($estudiante->foto) {
            Storage::disk('local')->delete($estudiante->foto);
        }
    }
}
