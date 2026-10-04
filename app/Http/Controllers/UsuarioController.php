<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\UsuarioRequest;
use App\Models\Asistencia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', [
            'usuarios' => User::query()->orderBy('rol')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.form', [
            'usuario' => new User(['activo' => true, 'rol' => Rol::Guarda]),
            'roles' => Rol::cases(),
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('usuarios.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'roles' => Rol::cases(),
        ]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }

        if ($usuario->is($request->user())) {
            // Evita que el administrador se quite a sí mismo el acceso.
            $datos['rol'] = Rol::Admin;
            $datos['activo'] = true;
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado.');
    }

    /**
     * Un usuario que ya registró marcas se desactiva (para conservar la auditoría) en lugar de eliminarse.
     */
    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puede eliminar su propio usuario.']);
        }

        $tieneMarcas = Asistencia::query()
            ->where('usuario_id', $usuario->id)
            ->orWhere('usuario_salida_id', $usuario->id)
            ->exists();

        if ($tieneMarcas) {
            $usuario->update(['activo' => false]);

            return redirect()->route('usuarios.index')->with('status', 'El usuario tiene marcas registradas, por lo que se desactivó en lugar de eliminarse.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('status', 'Usuario eliminado.');
    }
}
