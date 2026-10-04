<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteRequest;
use App\Models\Asistencia;
use App\Models\Seccion;
use App\Models\Subgrupo;
use App\Services\ExportadorExcel;
use App\Services\RegistroAsistencia;
use App\Services\ReporteAsistencia;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteController extends Controller
{
    public function __construct(
        private ReporteAsistencia $reportes,
        private ExportadorExcel $exportador,
    ) {}

    public function index(): View
    {
        return view('reportes.index');
    }

    /**
     * Informe de un estudiante (buscado por cédula o código de barras) en cualquier período.
     */
    public function estudiante(ReporteRequest $request, RegistroAsistencia $registro): View|BinaryFileResponse
    {
        $estudiante = $request->filled('cedula') ? $registro->buscarEstudiante($request->input('cedula')) : null;
        $reporte = $estudiante ? $this->reportes->porEstudiante($estudiante, $request->desde(), $request->hasta()) : null;

        if ($estudiante && $request->quiereExcel()) {
            $resumen = $reporte['resumen'];

            return $this->exportador->descargar(
                "asistencia_{$estudiante->cedula}",
                [
                    "Informe de asistencia — {$estudiante->nombreCompleto()}",
                    "Cédula: {$estudiante->cedula}   Sección: {$estudiante->seccionNombre()}",
                    'Período: '.$request->desde()->format('d/m/Y').' al '.$request->hasta()->format('d/m/Y'),
                    "Días lectivos: {$resumen['lectivos']}   Asistencias: {$resumen['presentes']}   Tardías: {$resumen['tardias']}   Ausencias: {$resumen['ausencias']}   Asistencia: {$resumen['porcentaje']}%",
                ],
                ['Fecha', 'Día', 'Estado', 'Entrada', 'Salida', 'Registrado por'],
                collect($reporte['dias'])->map(fn (array $dia) => [
                    $dia['fecha']->format('d/m/Y'),
                    ucfirst($dia['fecha']->translatedFormat('l')),
                    $dia['estado'].($dia['lectivo'] ? '' : ' (día no lectivo)'),
                    $dia['asistencia'] ? Asistencia::formatoHora($dia['asistencia']->hora_entrada) : '',
                    $dia['asistencia']?->hora_salida ? Asistencia::formatoHora($dia['asistencia']->hora_salida) : '',
                    $dia['asistencia']?->usuario?->name ?? '',
                ]),
            );
        }

        return view('reportes.estudiante', [
            'estudiante' => $estudiante,
            'reporte' => $reporte,
            'desde' => $request->desde(),
            'hasta' => $request->hasta(),
        ]);
    }

    /**
     * Totales por estudiante de una sección, un subgrupo o todo el colegio.
     */
    public function grupo(ReporteRequest $request): View|BinaryFileResponse
    {
        $seccionId = $request->integer('seccion_id') ?: null;
        $subgrupoId = $request->integer('subgrupo_id') ?: null;
        $reporte = $this->reportes->porGrupo($seccionId, $subgrupoId, $request->desde(), $request->hasta());
        $titulo = $this->nombreGrupo($seccionId, $subgrupoId);

        if ($request->quiereExcel()) {
            return $this->exportador->descargar(
                'asistencia_'.str($titulo)->slug('_'),
                [
                    "Informe de asistencia — {$titulo}",
                    'Período: '.$request->desde()->format('d/m/Y').' al '.$request->hasta()->format('d/m/Y')."   Días lectivos: {$reporte['lectivos']}",
                ],
                ['Cédula', 'Estudiante', 'Sección', 'Días lectivos', 'Asistencias', 'Tardías', 'Ausencias', '% Asistencia'],
                $reporte['filas']->map(fn (array $fila) => [
                    $fila['estudiante']->cedula,
                    $fila['estudiante']->nombreCompleto(),
                    $fila['estudiante']->seccionNombre(),
                    $fila['lectivos'],
                    $fila['presentes'],
                    $fila['tardias'],
                    $fila['ausencias'],
                    $fila['porcentaje'],
                ]),
            );
        }

        return view('reportes.grupo', [
            'reporte' => $reporte,
            'titulo' => $titulo,
            'secciones' => $this->listaSecciones(),
            'desde' => $request->desde(),
            'hasta' => $request->hasta(),
        ]);
    }

    /**
     * Comparativo entre todas las secciones.
     */
    public function secciones(ReporteRequest $request): View|BinaryFileResponse
    {
        $reporte = $this->reportes->porSecciones($request->desde(), $request->hasta());

        if ($request->quiereExcel()) {
            return $this->exportador->descargar(
                'asistencia_por_seccion',
                [
                    'Asistencia por sección',
                    'Período: '.$request->desde()->format('d/m/Y').' al '.$request->hasta()->format('d/m/Y')."   Días lectivos: {$reporte['lectivos']}",
                ],
                ['Sección', 'Nivel', 'Estudiantes', 'Asistencias', 'Tardías', 'Ausencias', '% Asistencia'],
                $reporte['filas']->map(fn (array $fila) => [
                    $fila['seccion']->nombre,
                    $fila['seccion']->nivel,
                    $fila['estudiantes'],
                    $fila['presentes'],
                    $fila['tardias'],
                    $fila['ausencias'],
                    $fila['porcentaje'],
                ]),
            );
        }

        return view('reportes.secciones', [
            'reporte' => $reporte,
            'desde' => $request->desde(),
            'hasta' => $request->hasta(),
        ]);
    }

    /**
     * Lista de un día: quién llegó (y a qué hora), quién llegó tarde y quién está ausente.
     */
    public function diario(ReporteRequest $request): View|BinaryFileResponse
    {
        $seccionId = $request->integer('seccion_id') ?: null;
        $subgrupoId = $request->integer('subgrupo_id') ?: null;
        $fecha = $request->fecha();
        $reporte = $this->reportes->diario($fecha, $seccionId, $subgrupoId);
        $titulo = $this->nombreGrupo($seccionId, $subgrupoId);

        if ($request->quiereExcel()) {
            return $this->exportador->descargar(
                'asistencia_'.$fecha->format('Y-m-d').'_'.str($titulo)->slug('_'),
                ["Asistencia del {$fecha->format('d/m/Y')} — {$titulo}"],
                ['Cédula', 'Estudiante', 'Sección', 'Estado', 'Entrada', 'Salida'],
                $reporte['filas']->map(fn (array $fila) => [
                    $fila['estudiante']->cedula,
                    $fila['estudiante']->nombreCompleto(),
                    $fila['estudiante']->seccionNombre(),
                    $fila['asistencia'] ? $fila['asistencia']->estado->etiqueta() : 'Ausente',
                    $fila['asistencia'] ? Asistencia::formatoHora($fila['asistencia']->hora_entrada) : '',
                    $fila['asistencia']?->hora_salida ? Asistencia::formatoHora($fila['asistencia']->hora_salida) : '',
                ]),
            );
        }

        return view('reportes.diario', [
            'reporte' => $reporte,
            'titulo' => $titulo,
            'fecha' => $fecha,
            'secciones' => $this->listaSecciones(),
        ]);
    }

    private function nombreGrupo(?int $seccionId, ?int $subgrupoId): string
    {
        if ($subgrupoId && $subgrupo = Subgrupo::query()->with('seccion')->find($subgrupoId)) {
            return 'Sección '.$subgrupo->nombreCompleto();
        }

        if ($seccionId && $seccion = Seccion::query()->find($seccionId)) {
            return "Sección {$seccion->nombre}";
        }

        return 'Todo el colegio';
    }

    private function listaSecciones()
    {
        return Seccion::query()->with('subgrupos')->get()->sortBy('nombre', SORT_NATURAL)->values();
    }
}
