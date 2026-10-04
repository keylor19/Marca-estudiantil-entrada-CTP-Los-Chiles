<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeccionController extends Controller
{
    public function index(): View
    {
        return view('secciones.index', [
            'secciones' => Seccion::query()
                ->with(['subgrupos' => fn ($query) => $query->withCount('estudiantes')])
                ->get()
                ->sortBy('nombre', SORT_NATURAL)
                ->values(),
        ]);
    }

    public function create(): View
    {
        return view('secciones.form', ['seccion' => new Seccion(['activo' => true])]);
    }

    /**
     * Cada sección se crea con sus dos subgrupos (A y B), salvo que se indique lo contrario.
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        $seccion = Seccion::create($datos);

        if ($request->boolean('crear_subgrupos')) {
            $seccion->subgrupos()->createMany([['nombre' => 'A'], ['nombre' => 'B']]);
        }

        return redirect()->route('secciones.edit', $seccion)->with('status', 'Sección creada.');
    }

    public function edit(Seccion $seccion): View
    {
        $seccion->load(['subgrupos' => fn ($query) => $query->withCount('estudiantes')]);

        return view('secciones.form', ['seccion' => $seccion]);
    }

    public function update(Request $request, Seccion $seccion): RedirectResponse
    {
        $seccion->update($this->validar($request, $seccion));

        return redirect()->route('secciones.edit', $seccion)->with('status', 'Sección actualizada.');
    }

    public function destroy(Seccion $seccion): RedirectResponse
    {
        if ($seccion->estudiantes()->exists()) {
            return back()->withErrors(['seccion' => 'No se puede eliminar una sección que tiene estudiantes. Muévalos a otra sección o desactive la sección.']);
        }

        $seccion->delete();

        return redirect()->route('secciones.index')->with('status', 'Sección eliminada.');
    }

    /**
     * @return array{nombre: string, nivel: string, activo: bool}
     */
    private function validar(Request $request, ?Seccion $seccion = null): array
    {
        $request->merge([
            'nombre' => strtoupper(str_replace(' ', '', (string) $request->input('nombre'))),
            'activo' => $request->boolean('activo'),
        ]);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:20', Rule::unique('secciones', 'nombre')->ignore($seccion)],
            'nivel' => ['required', 'string', 'max:50'],
            'activo' => ['boolean'],
        ]);
    }
}
