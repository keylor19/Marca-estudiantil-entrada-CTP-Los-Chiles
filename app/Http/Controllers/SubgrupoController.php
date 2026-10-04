<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use App\Models\Subgrupo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubgrupoController extends Controller
{
    public function store(Request $request, Seccion $seccion): RedirectResponse
    {
        $seccion->subgrupos()->create($this->validar($request, $seccion));

        return redirect()->route('secciones.edit', $seccion)->with('status', 'Subgrupo agregado.');
    }

    public function update(Request $request, Subgrupo $subgrupo): RedirectResponse
    {
        $subgrupo->update($this->validar($request, $subgrupo->seccion, $subgrupo));

        return redirect()->route('secciones.edit', $subgrupo->seccion_id)->with('status', 'Subgrupo actualizado.');
    }

    public function destroy(Subgrupo $subgrupo): RedirectResponse
    {
        if ($subgrupo->estudiantes()->exists()) {
            return back()->withErrors(['subgrupo' => 'No se puede eliminar un subgrupo que tiene estudiantes.']);
        }

        $subgrupo->delete();

        return redirect()->route('secciones.edit', $subgrupo->seccion_id)->with('status', 'Subgrupo eliminado.');
    }

    /**
     * @return array{nombre: string, especialidad: string|null}
     */
    private function validar(Request $request, Seccion $seccion, ?Subgrupo $subgrupo = null): array
    {
        $request->merge(['nombre' => strtoupper(trim((string) $request->input('nombre')))]);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:10', Rule::unique('subgrupos', 'nombre')->where('seccion_id', $seccion->id)->ignore($subgrupo)],
            'especialidad' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
