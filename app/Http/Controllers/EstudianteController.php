<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstudianteRequest;
use App\Models\Estudiante;
use App\Models\Seccion;
use App\Services\FotoEstudiante;
use App\Services\ReporteAsistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EstudianteController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'activos');

        $estudiantes = Estudiante::query()
            ->with('subgrupo.seccion')
            ->buscar($request->query('q'))
            ->when($request->integer('seccion_id'), fn ($query, int $seccionId) => $query->deSeccion($seccionId))
            ->when($estado === 'activos', fn ($query) => $query->where('activo', true))
            ->when($estado === 'inactivos', fn ($query) => $query->where('activo', false))
            ->when($estado === 'vencidos', fn ($query) => $query->activos()->matriculaVencida())
            ->ordenAlfabetico()
            ->paginate(25)
            ->withQueryString();

        return view('estudiantes.index', [
            'estudiantes' => $estudiantes,
            'secciones' => Seccion::ordenadas(),
            'estado' => $estado,
            'sinMatricula' => Estudiante::query()->activos()->matriculaVencida()->count(),
            'cursoLectivo' => Estudiante::cursoLectivo(),
        ]);
    }

    /**
     * Al cerrar la matrícula del año: deshabilita a los activos que no quedaron matriculados.
     * No se borran, para conservar su historial de asistencia.
     */
    public function deshabilitarSinMatricula(): RedirectResponse
    {
        $cantidad = Estudiante::query()->activos()->matriculaVencida()->update(['activo' => false]);

        return redirect()->route('estudiantes.index', ['estado' => 'inactivos'])
            ->with('status', "Se deshabilitaron {$cantidad} estudiante(s) que no están en la matrícula ".Estudiante::cursoLectivo().'.');
    }

    public function create(): View
    {
        return view('estudiantes.form', [
            'estudiante' => new Estudiante(['activo' => true, 'anio_matricula' => Estudiante::cursoLectivo()]),
            'secciones' => $this->seccionesConSubgrupos(),
        ]);
    }

    public function store(EstudianteRequest $request, FotoEstudiante $fotos): RedirectResponse
    {
        $estudiante = Estudiante::create($request->safe()->except('foto'));

        if ($request->hasFile('foto')) {
            $fotos->guardar($estudiante, $request->file('foto')->get());
        }

        return redirect()->route('estudiantes.show', $estudiante)
            ->with('status', "Estudiante registrado. Código de barras: {$estudiante->codigo_barra}");
    }

    public function show(Estudiante $estudiante, ReporteAsistencia $reporte): View
    {
        $estudiante->load('subgrupo.seccion');

        return view('estudiantes.show', [
            'estudiante' => $estudiante,
            'avisos' => $estudiante->avisos()->latest()->limit(10)->get(),
            'reporte' => $reporte->porEstudiante($estudiante, today()->subDays(29), today()),
        ]);
    }

    public function edit(Estudiante $estudiante): View
    {
        return view('estudiantes.form', [
            'estudiante' => $estudiante,
            'secciones' => $this->seccionesConSubgrupos(),
        ]);
    }

    public function update(EstudianteRequest $request, Estudiante $estudiante, FotoEstudiante $fotos): RedirectResponse
    {
        $estudiante->update($request->safe()->except('foto'));

        if ($request->hasFile('foto')) {
            $fotos->guardar($estudiante, $request->file('foto')->get());
        }

        return redirect()->route('estudiantes.show', $estudiante)->with('status', 'Datos del estudiante actualizados.');
    }

    /**
     * Si el estudiante ya tiene marcas no se borra (se perdería el historial): se desactiva.
     */
    public function destroy(Estudiante $estudiante, FotoEstudiante $fotos): RedirectResponse
    {
        if ($estudiante->asistencias()->exists()) {
            $estudiante->update(['activo' => false]);

            return redirect()->route('estudiantes.index')
                ->with('status', 'El estudiante tiene asistencias registradas, por lo que se desactivó en lugar de eliminarse.');
        }

        $fotos->eliminar($estudiante);
        $estudiante->delete();

        return redirect()->route('estudiantes.index')->with('status', 'Estudiante eliminado.');
    }

    /**
     * Sirve la foto desde el disco privado (solo usuarios autenticados pueden verla).
     */
    public function foto(Estudiante $estudiante): Response
    {
        $disco = Storage::disk('local');

        if ($estudiante->foto && $disco->exists($estudiante->foto)) {
            return $disco->response($estudiante->foto, null, ['Cache-Control' => 'private, max-age=86400']);
        }

        return response()->file(public_path('img/sin-foto.svg'), ['Cache-Control' => 'private, max-age=3600']);
    }

    private function seccionesConSubgrupos()
    {
        return Seccion::query()->with('subgrupos')->get()->sortBy('nombre', SORT_NATURAL)->values();
    }
}
