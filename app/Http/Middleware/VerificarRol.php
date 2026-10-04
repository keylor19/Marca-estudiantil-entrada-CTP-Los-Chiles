<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    /**
     * Permite el acceso solo a los roles indicados, ej. `rol:admin,direccion`.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if ($request->user() && ! $request->user()->activo) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['usuario' => 'Su usuario está desactivado.']);
        }

        $permitidos = array_map(fn (string $rol) => Rol::from($rol), $roles);

        abort_unless($request->user()?->tieneRol(...$permitidos), 403, 'No tiene permiso para acceder a esta sección.');

        return $next($request);
    }
}
