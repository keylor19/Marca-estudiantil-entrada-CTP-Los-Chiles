<x-app-layout titulo="Informe por estudiante">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Informe por estudiante</h2>
        <div class="flex gap-2">
            @if ($estudiante)
                <x-boton-enlace variante="excel" :href="request()->fullUrlWithQuery(['formato' => 'excel'])">Descargar Excel</x-boton-enlace>
                <x-secondary-button onclick="window.print()" class="print:hidden">Imprimir</x-secondary-button>
            @endif
            <x-boton-enlace variante="secundario" :href="route('reportes.index')">Informes</x-boton-enlace>
        </div>
    </x-slot>

    <x-tarjeta class="print:hidden">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <x-input-label for="cedula" value="Cédula (o código del carnet)" />
                <x-text-input id="cedula" name="cedula" :value="request('cedula')" class="mt-1 block" required autofocus placeholder="2-0876-0123" />
            </div>
            <x-filtro-periodo :desde="$desde" :hasta="$hasta" />
            <x-primary-button>Buscar</x-primary-button>
        </form>
    </x-tarjeta>

    @if (request()->filled('cedula') && ! $estudiante)
        <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-900">No se encontró ningún estudiante con la cédula «{{ request('cedula') }}».</div>
    @endif

    @if ($estudiante)
        @php($resumen = $reporte['resumen'])
        <x-tarjeta>
            <div class="flex flex-wrap gap-6 items-center">
                <img src="{{ $estudiante->fotoUrl() }}" alt="" class="h-28 w-auto aspect-[3/4] object-cover rounded bg-slate-200">
                <div class="text-sm space-y-1">
                    <div class="text-lg font-bold text-slate-900">{{ $estudiante->nombreCompleto() }}</div>
                    <div><span class="text-slate-500">Cédula:</span> {{ $estudiante->cedula }}</div>
                    <div><span class="text-slate-500">Sección:</span> {{ $estudiante->seccionNombre() }}</div>
                    <div><span class="text-slate-500">Período:</span> {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</div>
                </div>
            </div>
        </x-tarjeta>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <x-dato etiqueta="Días lectivos" :valor="$resumen['lectivos']" />
            <x-dato etiqueta="✓ Asistencias" :valor="$resumen['presentes']" detalle="incluye tardías" />
            <x-dato etiqueta="⏱ Tardías" :valor="$resumen['tardias']" />
            <x-dato etiqueta="✕ Ausencias" :valor="$resumen['ausencias']" />
            <x-dato etiqueta="% de asistencia" :valor="$resumen['porcentaje'].'%'" />
        </div>

        <x-tarjeta titulo="Detalle por día">
            @include('reportes.partials.tabla-dias', ['dias' => $reporte['dias']])
        </x-tarjeta>
    @endif
</x-app-layout>
