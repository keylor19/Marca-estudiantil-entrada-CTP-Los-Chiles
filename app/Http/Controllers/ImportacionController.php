<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Services\ExportadorExcel;
use App\Services\FotoEstudiante;
use App\Services\ImportadorEstudiantes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class ImportacionController extends Controller
{
    public function create(): View
    {
        return view('estudiantes.importar', [
            'anios' => $this->aniosMatricula(),
        ]);
    }

    /**
     * Cada año se sube la matrícula nueva: se actualiza por cédula y queda matriculado en el año elegido.
     */
    public function store(Request $request, ImportadorEstudiantes $importador): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
            'anio_matricula' => ['nullable', 'integer', Rule::in($this->aniosMatricula())],
        ]);

        $archivo = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension()) === 'xlsx' ? 'xlsx' : 'csv';
        $anio = $request->integer('anio_matricula') ?: Estudiante::cursoLectivo();

        return redirect()->route('estudiantes.importar')
            ->with('resultado', $importador->importar($archivo->getRealPath(), $extension, $anio))
            ->with('anio_matricula', $anio)
            ->with('sin_matricula', Estudiante::query()->activos()->matriculaVencida()->count());
    }

    /**
     * El año en curso, o el siguiente cuando la matrícula se hace antes de que termine el año.
     *
     * @return list<int>
     */
    private function aniosMatricula(): array
    {
        return [Estudiante::cursoLectivo(), Estudiante::cursoLectivo() + 1];
    }

    public function plantilla(ExportadorExcel $exportador): BinaryFileResponse
    {
        return $exportador->descargar('plantilla_estudiantes', [], [
            'cedula', 'nombre', 'apellido1', 'apellido2', 'seccion', 'subgrupo', 'especialidad', 'fecha_nacimiento',
            'encargado', 'parentesco', 'telefono_encargado', 'correo_encargado',
        ], [
            ['2-0876-0123', 'María José', 'Rodríguez', 'Pérez', '10-2', 'A', 'Contabilidad', '15/03/2010', 'Ana Pérez Mora', 'Madre', '8888-7777', 'ana.perez@correo.com'],
            ['2-0880-0456', 'Carlos', 'Soto', 'Vargas', '10-2', 'B', 'Informática', '02/11/2009', 'Luis Soto Rojas', 'Padre', '8765-4321', ''],
        ]);
    }

    public function fotos(): View
    {
        return view('estudiantes.fotos');
    }

    /**
     * Carga masiva de fotos: cada archivo debe llamarse con la cédula del estudiante (ej. 2-0876-0123.jpg).
     * Acepta varias imágenes o un .zip con todas.
     */
    public function guardarFotos(Request $request, FotoEstudiante $fotos): RedirectResponse
    {
        $request->validate([
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'zip' => ['nullable', 'file', 'mimes:zip', 'max:204800'],
        ]);

        $archivos = [];

        foreach ($request->file('fotos', []) as $foto) {
            $archivos[$foto->getClientOriginalName()] = fn () => $foto->get();
        }

        if ($request->hasFile('zip')) {
            $zip = new ZipArchive;
            $zip->open($request->file('zip')->getRealPath());

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nombre = $zip->getNameIndex($i);

                if (preg_match('/\.(jpe?g|png|webp)$/i', $nombre) && ! str_starts_with(basename($nombre), '.')) {
                    $archivos[basename($nombre)] = fn () => $zip->getFromIndex($i);
                }
            }
        }

        $asignadas = 0;
        $sinEstudiante = [];

        foreach ($archivos as $nombre => $contenido) {
            $identificador = pathinfo($nombre, PATHINFO_FILENAME);
            $estudiante = Estudiante::query()
                ->where('codigo_barra', strtoupper($identificador))
                ->orWhereRaw("REPLACE(REPLACE(cedula, '-', ''), ' ', '') = ?", [str_replace(['-', ' '], '', $identificador)])
                ->first();

            if (! $estudiante) {
                $sinEstudiante[] = $nombre;

                continue;
            }

            try {
                $fotos->guardar($estudiante, $contenido());
                $asignadas++;
            } catch (Throwable) {
                $sinEstudiante[] = "{$nombre} (imagen dañada)";
            }
        }

        if (isset($zip)) {
            $zip->close();
        }

        return redirect()->route('estudiantes.fotos')->with('resultado', [
            'asignadas' => $asignadas,
            'sin_estudiante' => $sinEstudiante,
        ]);
    }
}
