<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Estudiante;
use App\Models\Seccion;
use App\Services\GeneradorCodigoBarras;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CarnetController extends Controller
{
    /**
     * Carnets por hoja carta => columnas de la cuadrícula (caben como máximo 3 × 3 tarjetas CR80).
     */
    public const DISTRIBUCIONES = [1 => 1, 2 => 2, 3 => 3, 4 => 2, 6 => 3, 9 => 3];

    /**
     * Selección de los carnets a imprimir (por sección, subgrupo o estudiantes específicos).
     */
    public function index(): View
    {
        return view('carnets.index', [
            'secciones' => Seccion::query()->with('subgrupos')->withCount('estudiantes')->get()->sortBy('nombre', SORT_NATURAL)->values(),
            'distribuciones' => array_keys(self::DISTRIBUCIONES),
        ]);
    }

    /**
     * Hoja lista para imprimir: de 1 a 9 carnets tamaño tarjeta (CR80) por página carta.
     */
    public function imprimir(Request $request, GeneradorCodigoBarras $codigos): View
    {
        $request->validate([
            'seccion_id' => ['nullable', 'integer'],
            'subgrupo_id' => ['nullable', 'integer'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'por_hoja' => ['nullable', 'integer', Rule::in(array_keys(self::DISTRIBUCIONES))],
        ]);

        $porHoja = $request->integer('por_hoja') ?: 9;

        $estudiantes = Estudiante::query()
            ->with('subgrupo.seccion')
            ->when($request->filled('ids'), fn ($query) => $query->whereIn('id', $request->input('ids')))
            ->when(! $request->filled('ids'), fn ($query) => $query->activos()->matriculaVigente())
            ->when($request->integer('subgrupo_id'), fn ($query, int $subgrupoId) => $query->where('subgrupo_id', $subgrupoId))
            ->when($request->integer('seccion_id') && ! $request->integer('subgrupo_id'), fn ($query) => $query->deSeccion($request->integer('seccion_id')))
            ->when(! $request->filled('ids') && ! $request->integer('seccion_id') && ! $request->integer('subgrupo_id') && ! $request->boolean('todos'), fn ($query) => $query->whereRaw('1 = 0'))
            ->ordenAlfabetico()
            ->get();

        return view('carnets.imprimir', [
            'estudiantes' => $estudiantes,
            'hojas' => $estudiantes->chunk($porHoja),
            'porHoja' => $porHoja,
            'columnas' => self::DISTRIBUCIONES[$porHoja],
            'distribuciones' => array_keys(self::DISTRIBUCIONES),
            'codigos' => $estudiantes->mapWithKeys(fn (Estudiante $estudiante) => [$estudiante->id => $codigos->svg($estudiante->codigo_barra, 2, 50)]),
            'configuracion' => Configuracion::actual(),
            'curso' => Estudiante::cursoLectivo(),
        ]);
    }
}
