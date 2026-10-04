<x-app-layout titulo="Lista del día">
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-900">Lista del día — {{ $titulo }}</h2>
            <p class="text-sm text-slate-500">{{ ucfirst($fecha->translatedFormat('l j \d\e F \d\e Y')) }}</p>
        </div>
        <div class="flex gap-2">
            <x-boton-enlace variante="excel" :href="request()->fullUrlWithQuery(['formato' => 'excel'])">Descargar Excel</x-boton-enlace>
            <x-secondary-button onclick="window.print()" class="print:hidden">Imprimir</x-secondary-button>
        </div>
    </x-slot>

    <x-tarjeta class="print:hidden">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <x-input-label for="fecha" value="Fecha" />
                <x-text-input id="fecha" name="fecha" type="date" :value="$fecha->toDateString()" class="mt-1 block" required />
            </div>
            <x-selector-grupo :secciones="$secciones" />
            <x-primary-button>Ver</x-primary-button>
        </form>
    </x-tarjeta>

    @unless ($reporte['lectivo'])
        <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">ⓘ Esta fecha no es un día lectivo.</div>
    @endunless

    @php($totales = $reporte['totales'])
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-dato etiqueta="Estudiantes" :valor="$totales['estudiantes']" />
        <x-dato etiqueta="✓ Presentes" :valor="$totales['presentes']" />
        <x-dato etiqueta="⏱ Tardías" :valor="$totales['tardias']" detalle="incluidas en presentes" />
        <x-dato etiqueta="✕ Ausentes" :valor="$totales['ausentes']" />
    </div>

    <div x-data="{ filtro: 'todos' }" class="space-y-3">
        <div class="flex gap-1 print:hidden" role="group" aria-label="Filtrar lista">
            @foreach (['todos' => 'Todos', 'presentes' => 'Presentes', 'tardias' => 'Tardías', 'ausentes' => 'Ausentes'] as $valor => $texto)
                <button type="button" @click="filtro = '{{ $valor }}'" :class="filtro === '{{ $valor }}' ? 'bg-ctp-800 text-white' : 'bg-white text-slate-700'" class="rounded-full border border-slate-300 px-3 py-1 text-sm font-medium">{{ $texto }}</button>
            @endforeach
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Cédula</th>
                        <th class="px-4 py-3 font-semibold">Estudiante</th>
                        <th class="px-4 py-3 font-semibold">Sección</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold">Entrada</th>
                        <th class="px-4 py-3 font-semibold">Salida</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reporte['filas'] as $fila)
                        @php($categoria = ! $fila['asistencia'] ? 'ausentes' : ($fila['asistencia']->estado === \App\Enums\EstadoAsistencia::Tarde ? 'tardias' : 'presentes'))
                        <tr x-show="filtro === 'todos' || filtro === '{{ $categoria }}' || (filtro === 'presentes' && '{{ $categoria }}' === 'tardias')">
                            <td class="px-4 py-2 tabular-nums">{{ $fila['estudiante']->cedula }}</td>
                            <td class="px-4 py-2">{{ $fila['estudiante']->nombreCompleto() }}</td>
                            <td class="px-4 py-2">{{ $fila['estudiante']->seccionNombre() }}</td>
                            <td class="px-4 py-2"><x-estado-asistencia :asistencia="$fila['asistencia']" /></td>
                            <td class="px-4 py-2 tabular-nums">{{ \App\Models\Asistencia::formatoHora($fila['asistencia']?->hora_entrada) }}</td>
                            <td class="px-4 py-2 tabular-nums">{{ \App\Models\Asistencia::formatoHora($fila['asistencia']?->hora_salida) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay estudiantes en este grupo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
