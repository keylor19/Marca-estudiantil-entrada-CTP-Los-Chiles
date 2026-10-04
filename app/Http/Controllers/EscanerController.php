<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Services\RegistroAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EscanerController extends Controller
{
    /**
     * Pantalla del guarda: un campo siempre enfocado que recibe lo que "escribe" el lector de barras.
     */
    public function index(): View
    {
        $hoy = today()->toDateString();

        return view('escaner.index', [
            'configuracion' => Configuracion::actual(),
            'totalHoy' => Asistencia::query()->where('fecha', $hoy)->count(),
            'ultimas' => Asistencia::query()
                ->with('estudiante.subgrupo.seccion')
                ->where('fecha', $hoy)
                ->latest('updated_at')
                ->limit(8)
                ->get()
                ->map(fn (Asistencia $asistencia) => [
                    'nombre' => $asistencia->estudiante->nombreCompleto(),
                    'seccion' => $asistencia->estudiante->seccionNombre(),
                    'hora' => Asistencia::formatoHora($asistencia->hora_salida ?? $asistencia->hora_entrada),
                    'tipo' => $asistencia->hora_salida ? 'Salida' : 'Entrada',
                ]),
        ]);
    }

    public function registrar(Request $request, RegistroAsistencia $registro): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:50'],
            'modo' => ['nullable', Rule::in([RegistroAsistencia::MODO_ENTRADA, RegistroAsistencia::MODO_SALIDA])],
        ]);

        $modo = Configuracion::actual()->registrar_salida
            ? ($datos['modo'] ?? RegistroAsistencia::MODO_ENTRADA)
            : RegistroAsistencia::MODO_ENTRADA;

        $resultado = $registro->registrar($datos['codigo'], $request->user(), $modo);

        return response()->json([
            ...$resultado,
            'total_hoy' => Asistencia::query()->where('fecha', today()->toDateString())->count(),
        ]);
    }
}
