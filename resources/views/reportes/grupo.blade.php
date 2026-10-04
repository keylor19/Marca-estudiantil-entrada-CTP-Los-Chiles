<x-app-layout titulo="Informe por sección">
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-900">Informe por sección — {{ $titulo }}</h2>
            <p class="text-sm text-slate-500">{{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} · {{ $reporte['lectivos'] }} días lectivos</p>
        </div>
        <div class="flex gap-2">
            <x-boton-enlace variante="excel" :href="request()->fullUrlWithQuery(['formato' => 'excel'])">Descargar Excel</x-boton-enlace>
            <x-secondary-button onclick="window.print()" class="print:hidden">Imprimir</x-secondary-button>
        </div>
    </x-slot>

    <x-tarjeta class="print:hidden">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <x-selector-grupo :secciones="$secciones" />
            <x-filtro-periodo :desde="$desde" :hasta="$hasta" />
            <x-primary-button>Generar</x-primary-button>
        </form>
    </x-tarjeta>

    @php($totales = $reporte['totales'])
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-dato etiqueta="Estudiantes" :valor="$reporte['filas']->count()" />
        <x-dato etiqueta="✓ Asistencias" :valor="$totales['presentes']" :detalle="$totales['porcentaje'].'% de asistencia'" />
        <x-dato etiqueta="⏱ Tardías" :valor="$totales['tardias']" />
        <x-dato etiqueta="✕ Ausencias" :valor="$totales['ausencias']" />
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Cédula</th>
                    <th class="px-4 py-3 font-semibold">Estudiante</th>
                    <th class="px-4 py-3 font-semibold">Sección</th>
                    <th class="px-4 py-3 font-semibold text-right">Asistencias</th>
                    <th class="px-4 py-3 font-semibold text-right">Tardías</th>
                    <th class="px-4 py-3 font-semibold text-right">Ausencias</th>
                    <th class="px-4 py-3 font-semibold text-right">% Asistencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reporte['filas'] as $fila)
                    <tr>
                        <td class="px-4 py-2 tabular-nums">{{ $fila['estudiante']->cedula }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('reportes.estudiante', ['cedula' => $fila['estudiante']->cedula, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}" class="text-ctp-800 hover:underline">{{ $fila['estudiante']->nombreCompleto() }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $fila['estudiante']->seccionNombre() }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $fila['presentes'] }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $fila['tardias'] }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $fila['ausencias'] }}</td>
                        <td class="px-4 py-2 text-right tabular-nums font-semibold">{{ $fila['porcentaje'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No hay estudiantes en este grupo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
