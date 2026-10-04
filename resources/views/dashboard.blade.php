<x-app-layout titulo="Inicio">
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-900">Resumen de asistencia</h2>
            <p class="text-sm text-slate-500">{{ ucfirst(today()->translatedFormat('l j \d\e F \d\e Y')) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (auth()->user()->esAdmin())
                <x-boton-enlace :href="route('escaner.index')">Abrir control de ingreso</x-boton-enlace>
            @endif
            <x-boton-enlace variante="secundario" :href="route('reportes.diario')">Lista del día</x-boton-enlace>
        </div>
    </x-slot>

    @unless ($diario['lectivo'])
        <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
            ⓘ Hoy no es un día lectivo según la configuración; las ausencias de hoy no cuentan en los informes.
        </div>
    @endunless

    @if ($sinMatricula > 0)
        <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
            ⚠ <strong>{{ $sinMatricula }}</strong> estudiante(s) activo(s) no están en la matrícula {{ \App\Models\Estudiante::cursoLectivo() }} y no pueden marcar la entrada.
            <a href="{{ route('estudiantes.index', ['estado' => 'vencidos']) }}" class="underline font-semibold">Revisar</a>
        </div>
    @endif

    @php($totales = $diario['totales'])
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-dato etiqueta="Estudiantes activos" :valor="$totales['estudiantes']" />
        <x-dato etiqueta="✓ Presentes" :valor="$totales['presentes']"
                :detalle="$totales['estudiantes'] ? round($totales['presentes'] / $totales['estudiantes'] * 100, 1).'% del total' : null" />
        <x-dato etiqueta="⏱ Llegadas tardías" :valor="$totales['tardias']" detalle="incluidas en presentes" />
        <x-dato etiqueta="✕ Ausentes" :valor="$totales['ausentes']" />
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <x-tarjeta titulo="Asistencia por sección" class="lg:col-span-2">
            @if ($porSeccion->isEmpty())
                <p class="text-sm text-slate-500">Aún no hay secciones ni estudiantes registrados.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($porSeccion as $fila)
                        @php($porcentaje = $fila['total'] ? $fila['presentes'] / $fila['total'] * 100 : 0)
                        <li>
                            <a href="{{ route('reportes.diario', ['seccion_id' => $fila['seccion_id']]) }}"
                               class="grid grid-cols-[4rem_1fr_7rem] items-center gap-3 py-2 hover:bg-slate-50 rounded"
                               title="{{ $fila['presentes'] }} de {{ $fila['total'] }} presentes ({{ round($porcentaje, 1) }}%)">
                                <span class="font-semibold text-slate-900">{{ $fila['seccion'] }}</span>
                                <span class="h-3 rounded bg-slate-100 overflow-hidden" aria-hidden="true">
                                    <span class="block h-full rounded bg-ctp-700" style="width: {{ $porcentaje }}%"></span>
                                </span>
                                <span class="text-sm text-slate-700 text-right tabular-nums">{{ $fila['presentes'] }}/{{ $fila['total'] }} presentes</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-tarjeta>

        <x-tarjeta titulo="Últimas entradas">
            <ul class="divide-y divide-slate-100">
                @forelse ($ultimas as $marca)
                    <li class="py-2 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-medium text-slate-900 truncate">{{ $marca->estudiante->nombreCompleto() }}</div>
                            <div class="text-xs text-slate-500">{{ $marca->estudiante->seccionNombre() }} · {{ \App\Models\Asistencia::formatoHora($marca->hora_entrada) }}</div>
                        </div>
                        <x-estado-asistencia :asistencia="$marca" />
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">Todavía no hay marcas hoy.</li>
                @endforelse
            </ul>
        </x-tarjeta>
    </div>
</x-app-layout>
