<x-app-layout titulo="Marcas">
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-900">Registro de marcas</h2>
            <p class="text-sm text-slate-500">Cada entrada y salida, con la hora y el usuario que la registró.</p>
        </div>
        <x-boton-enlace variante="excel" :href="request()->fullUrlWithQuery(['formato' => 'excel'])">Descargar Excel</x-boton-enlace>
    </x-slot>

    <x-tarjeta class="print:hidden">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <x-filtro-periodo :desde="$desde" :hasta="$hasta" />
            <div>
                <x-input-label for="seccion_id" value="Sección" />
                <x-select id="seccion_id" name="seccion_id" class="mt-1 block">
                    <option value="">Todas</option>
                    @foreach ($secciones as $seccion)
                        <option value="{{ $seccion->id }}" @selected(request('seccion_id') == $seccion->id)>{{ $seccion->nombre }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-input-label for="usuario_id" value="Registrado por" />
                <x-select id="usuario_id" name="usuario_id" class="mt-1 block">
                    <option value="">Cualquiera</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected(request('usuario_id') == $usuario->id)>{{ $usuario->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-input-label for="estado" value="Estado" />
                <x-select id="estado" name="estado" class="mt-1 block">
                    <option value="">Todos</option>
                    <option value="presente" @selected(request('estado') === 'presente')>A tiempo</option>
                    <option value="tarde" @selected(request('estado') === 'tarde')>Tardías</option>
                    <option value="con_salida" @selected(request('estado') === 'con_salida')>Con salida</option>
                </x-select>
            </div>
            <div>
                <x-input-label for="q" value="Estudiante" />
                <x-text-input id="q" name="q" :value="request('q')" class="mt-1 block" placeholder="Nombre o cédula" />
            </div>
            <x-primary-button>Filtrar</x-primary-button>
        </form>
    </x-tarjeta>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Fecha</th>
                    <th class="px-4 py-3 font-semibold">Estudiante</th>
                    <th class="px-4 py-3 font-semibold">Sección</th>
                    <th class="px-4 py-3 font-semibold">Entrada</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3 font-semibold">Salida</th>
                    <th class="px-4 py-3 font-semibold">Registrado por</th>
                    @if (auth()->user()->esAdmin())
                        <th class="px-4 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($registros as $registro)
                    <tr>
                        <td class="px-4 py-2 tabular-nums">{{ $registro->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('estudiantes.show', $registro->estudiante) }}" class="text-ctp-800 hover:underline">{{ $registro->estudiante->nombreCompleto() }}</a>
                            <div class="text-xs text-slate-500">{{ $registro->estudiante->cedula }}</div>
                        </td>
                        <td class="px-4 py-2">{{ $registro->estudiante->seccionNombre() }}</td>
                        <td class="px-4 py-2 tabular-nums">{{ \App\Models\Asistencia::formatoHora($registro->hora_entrada) }}</td>
                        <td class="px-4 py-2"><x-estado-asistencia :asistencia="$registro" /></td>
                        <td class="px-4 py-2 tabular-nums">{{ \App\Models\Asistencia::formatoHora($registro->hora_salida) }}</td>
                        <td class="px-4 py-2 text-slate-600">
                            {{ $registro->usuario?->name }}
                            @if ($registro->usuarioSalida)
                                <div class="text-xs">Salida: {{ $registro->usuarioSalida->name }}</div>
                            @endif
                        </td>
                        @if (auth()->user()->esAdmin())
                            <td class="px-4 py-2 text-right">
                                <form method="POST" action="{{ route('registros.destroy', $registro) }}" onsubmit="return confirm('¿Eliminar esta marca? El estudiante quedará como ausente ese día.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-red-700 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">No hay marcas con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $registros->links() }}</div>
</x-app-layout>
