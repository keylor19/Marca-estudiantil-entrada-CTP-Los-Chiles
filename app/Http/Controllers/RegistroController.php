<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\Asistencia;
use App\Models\Seccion;
use App\Models\User;
use App\Services\ExportadorExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistroController extends Controller
{
    /**
     * Bitácora de marcas: quién pasó, a qué hora y qué usuario lo registró.
     */
    public function index(Request $request, ExportadorExcel $exportador): View|BinaryFileResponse
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'seccion_id' => ['nullable', 'integer'],
            'usuario_id' => ['nullable', 'integer'],
            'estado' => ['nullable', 'in:presente,tarde,con_salida'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $desde = $request->date('desde') ?? today();
        $hasta = $request->date('hasta') ?? today();

        $consulta = Asistencia::query()
            ->with(['estudiante.subgrupo.seccion', 'usuario', 'usuarioSalida'])
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($request->integer('usuario_id'), fn ($query, int $usuarioId) => $query->where(fn ($query) => $query->where('usuario_id', $usuarioId)->orWhere('usuario_salida_id', $usuarioId)))
            ->when($request->input('estado') === 'con_salida', fn ($query) => $query->whereNotNull('hora_salida'))
            ->when(in_array($request->input('estado'), ['presente', 'tarde']), fn ($query) => $query->where('estado', $request->input('estado')))
            ->when($request->integer('seccion_id') || $request->filled('q'), fn ($query) => $query->whereHas('estudiante', fn ($estudiante) => $estudiante
                ->buscar($request->input('q'))
                ->when($request->integer('seccion_id'), fn ($estudiante, int $seccionId) => $estudiante->deSeccion($seccionId))))
            ->orderByDesc('fecha')
            ->orderByDesc('hora_entrada');

        if ($request->input('formato') === 'excel') {
            return $exportador->descargar(
                'registros_'.$desde->format('Y-m-d').'_'.$hasta->format('Y-m-d'),
                ['Registro de marcas del '.$desde->format('d/m/Y').' al '.$hasta->format('d/m/Y')],
                ['Fecha', 'Cédula', 'Estudiante', 'Sección', 'Entrada', 'Estado', 'Registró entrada', 'Salida', 'Registró salida'],
                $consulta->lazy()->map(fn (Asistencia $asistencia) => [
                    $asistencia->fecha->format('d/m/Y'),
                    $asistencia->estudiante->cedula,
                    $asistencia->estudiante->nombreCompleto(),
                    $asistencia->estudiante->seccionNombre(),
                    Asistencia::formatoHora($asistencia->hora_entrada),
                    $asistencia->estado->etiqueta(),
                    $asistencia->usuario?->name,
                    $asistencia->hora_salida ? Asistencia::formatoHora($asistencia->hora_salida) : '',
                    $asistencia->usuarioSalida?->name,
                ]),
            );
        }

        return view('registros.index', [
            'registros' => $consulta->paginate(50)->withQueryString(),
            'desde' => $desde,
            'hasta' => $hasta,
            'secciones' => Seccion::ordenadas(),
            'usuarios' => User::query()->whereIn('rol', [Rol::Guarda, Rol::Admin])->orderBy('name')->get(),
        ]);
    }

    /**
     * Elimina una marca hecha por error (solo administrador).
     */
    public function destroy(Asistencia $asistencia): RedirectResponse
    {
        $asistencia->delete();

        return back()->with('status', 'Marca eliminada.');
    }
}
