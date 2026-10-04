<x-app-layout titulo="Usuarios">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Usuarios del sistema</h2>
        <x-boton-enlace :href="route('usuarios.create')">+ Nuevo usuario</x-boton-enlace>
    </x-slot>

    <x-tarjeta>
        <ul class="text-sm text-slate-700 space-y-1">
            <li><strong>Administrador:</strong> acceso a todo (estudiantes, carnets, secciones, usuarios, configuración, informes y control de ingreso).</li>
            <li><strong>Dirección / Administración:</strong> consulta estudiantes, marcas, estadísticas e informes. No modifica datos.</li>
            <li><strong>Guarda:</strong> solo la pantalla de control de ingreso.</li>
        </ul>
    </x-tarjeta>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nombre</th>
                    <th class="px-4 py-3 font-semibold">Usuario</th>
                    <th class="px-4 py-3 font-semibold">Rol</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($usuarios as $usuario)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $usuario->name }}</td>
                        <td class="px-4 py-2 font-mono">{{ $usuario->usuario }}</td>
                        <td class="px-4 py-2">{{ $usuario->rol->etiqueta() }}</td>
                        <td class="px-4 py-2">{{ $usuario->activo ? '● Activo' : '○ Inactivo' }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('usuarios.edit', $usuario) }}" class="text-ctp-800 hover:underline">Editar</a>
                            @unless ($usuario->is(auth()->user()))
                                <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="inline" onsubmit="return confirm('¿Eliminar este usuario? Si ya registró marcas solo se desactivará.')">
                                    @csrf
                                    @method('DELETE')
                                    · <button class="text-red-700 hover:underline">Eliminar</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
