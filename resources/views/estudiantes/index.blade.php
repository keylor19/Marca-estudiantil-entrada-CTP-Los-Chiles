<x-app-layout titulo="Estudiantes">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Estudiantes</h2>
        @if (auth()->user()->esAdmin())
            <div class="flex flex-wrap gap-2">
                <x-boton-enlace variante="secundario" :href="route('estudiantes.importar')">Importar desde Excel</x-boton-enlace>
                <x-boton-enlace variante="secundario" :href="route('estudiantes.fotos')">Cargar fotos</x-boton-enlace>
                <x-boton-enlace :href="route('estudiantes.create')">+ Nuevo estudiante</x-boton-enlace>
            </div>
        @endif
    </x-slot>

    @if ($sinMatricula > 0)
        <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 flex flex-wrap items-center justify-between gap-3">
            <p>
                ⚠ <strong>{{ $sinMatricula }}</strong> estudiante(s) activo(s) no están en la matrícula {{ $cursoLectivo }} y no pueden marcar la entrada.
                Actualice sus datos (o vuelva a importar la matrícula) o deshabilítelos.
                @if ($estado !== 'vencidos')
                    <a href="{{ route('estudiantes.index', ['estado' => 'vencidos']) }}" class="underline font-semibold">Ver la lista</a>
                @endif
            </p>
            @if (auth()->user()->esAdmin())
                <form method="POST" action="{{ route('estudiantes.deshabilitar-sin-matricula') }}"
                      onsubmit="return confirm('¿Deshabilitar los {{ $sinMatricula }} estudiante(s) que no están en la matrícula {{ $cursoLectivo }}? Se conserva su historial de asistencia.')">
                    @csrf
                    <x-danger-button>Deshabilitar a los {{ $sinMatricula }}</x-danger-button>
                </form>
            @endif
        </div>
    @endif

    <x-tarjeta>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="grow min-w-60">
                <x-input-label for="q" value="Buscar (nombre, cédula o código)" />
                <x-text-input id="q" name="q" :value="request('q')" class="mt-1 block w-full" autofocus />
            </div>
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
                <x-input-label for="estado" value="Estado" />
                <x-select id="estado" name="estado" class="mt-1 block">
                    <option value="activos" @selected($estado === 'activos')>Activos</option>
                    <option value="vencidos" @selected($estado === 'vencidos')>Matrícula vencida</option>
                    <option value="inactivos" @selected($estado === 'inactivos')>Inactivos</option>
                    <option value="todos" @selected($estado === 'todos')>Todos</option>
                </x-select>
            </div>
            <x-primary-button>Buscar</x-primary-button>
        </form>
    </x-tarjeta>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Estudiante</th>
                    <th class="px-4 py-3 font-semibold">Cédula</th>
                    <th class="px-4 py-3 font-semibold">Sección</th>
                    <th class="px-4 py-3 font-semibold">Código de barras</th>
                    <th class="px-4 py-3 font-semibold">Matrícula</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($estudiantes as $estudiante)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-3">
                                <img src="{{ $estudiante->fotoUrl() }}" alt="" loading="lazy" class="h-10 w-8 rounded object-cover bg-slate-200">
                                <a href="{{ route('estudiantes.show', $estudiante) }}" class="font-medium text-ctp-800 hover:underline">{{ $estudiante->nombreCompleto() }}</a>
                            </div>
                        </td>
                        <td class="px-4 py-2 tabular-nums">{{ $estudiante->cedula }}</td>
                        <td class="px-4 py-2">{{ $estudiante->seccionNombre() }}</td>
                        <td class="px-4 py-2 font-mono">{{ $estudiante->codigo_barra }}</td>
                        <td class="px-4 py-2 tabular-nums">
                            @if ($estudiante->matriculaVigente())
                                {{ $estudiante->anio_matricula }}
                            @else
                                <span class="text-amber-800 font-semibold" title="No está en la matrícula {{ $cursoLectivo }}">⚠ {{ $estudiante->anio_matricula ?? 'Sin año' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if ($estudiante->activo)
                                <span class="text-emerald-800">● Activo</span>
                            @else
                                <span class="text-slate-500">○ Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('estudiantes.show', $estudiante) }}" class="text-ctp-800 hover:underline">Ver</a>
                            @if (auth()->user()->esAdmin())
                                · <a href="{{ route('estudiantes.edit', $estudiante) }}" class="text-ctp-800 hover:underline">Editar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No se encontraron estudiantes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $estudiantes->links() }}</div>
</x-app-layout>
