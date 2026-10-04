<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Services\ReporteAsistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Resumen de asistencia del día. El guarda va directo a la pantalla de ingreso.
     */
    public function __invoke(Request $request, ReporteAsistencia $reporte): View|RedirectResponse
    {
        if ($request->user()->esGuarda()) {
            return redirect()->route('escaner.index');
        }

        $hoy = today();
        $diario = $reporte->diario($hoy, null, null);

        $porSeccion = $diario['filas']
            ->groupBy(fn (array $fila) => $fila['estudiante']->subgrupo->seccion->nombre)
            ->map(fn ($filas, string $seccion) => [
                'seccion' => $seccion,
                'seccion_id' => $filas->first()['estudiante']->subgrupo->seccion_id,
                'total' => $filas->count(),
                'presentes' => $filas->whereNotNull('asistencia')->count(),
            ])
            ->sortKeys(SORT_NATURAL)
            ->values();

        $ultimas = Asistencia::query()
            ->with('estudiante.subgrupo.seccion')
            ->where('fecha', $hoy->toDateString())
            ->orderByDesc('hora_entrada')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'diario' => $diario,
            'porSeccion' => $porSeccion,
            'ultimas' => $ultimas,
            'sinMatricula' => Estudiante::query()->activos()->matriculaVencida()->count(),
        ]);
    }
}
