<x-app-layout titulo="Comparativo de secciones">
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-900">Comparativo de secciones</h2>
            <p class="text-sm text-slate-500">{{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} · {{ $reporte['lectivos'] }} días lectivos</p>
        </div>
        <div class="flex gap-2">
            <x-boton-enlace variante="excel" :href="request()->fullUrlWithQuery(['formato' => 'excel'])">Descargar Excel</x-boton-enlace>
            <x-secondary-button onclick="window.print()" class="print:hidden">Imprimir</x-secondary-button>
        </div>
    </x-slot>

    <x-tarjeta class="print:hidden">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <x-filtro-periodo :desde="$desde" :hasta="$hasta" />
            <x-primary-button>Generar</x-primary-button>
        </form>
    </x-tarjeta>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Sección</th>
                    <th class="px-4 py-3 font-semibold">Nivel</th>
                    <th class="px-4 py-3 font-semibold text-right">Estudiantes</th>
                    <th class="px-4 py-3 font-semibold text-right">Asistencias</th>
                    <th class="px-4 py-3 font-semibold text-right">Tardías</th>
                    <th class="px-4 py-3 font-semibold text-right">Ausencias</th>
                    <th class="px-4 py-3 font-semibold w-64">% Asistencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reporte['filas'] as $fila)
                    <tr>
                        <td class="px-4 py-2 font-semibold">
                            <a href="{{ route('reportes.grupo', ['seccion_id' => $fila['seccion']->id, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}" class="text-ctp-800 hover:underline">{{ $fila['seccion']->nombre }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $fila['seccion']->nivel }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $fila['estudiantes'] }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $fila['presentes'] }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $fila['tardias'] }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $fila['ausencias'] }}</td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-2" title="{{ $fila['porcentaje'] }}% de asistencia">
                                <span class="h-3 grow rounded bg-slate-100 overflow-hidden" aria-hidden="true">
                                    <span class="block h-full rounded bg-ctp-700" style="width: {{ $fila['porcentaje'] }}%"></span>
                                </span>
                                <span class="w-14 text-right tabular-nums font-semibold">{{ $fila['porcentaje'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No hay datos para el período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
