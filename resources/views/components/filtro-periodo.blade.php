@props(['desde', 'hasta'])

{{-- Período libre + atajos: hoy, esta semana, este mes, últimos 3 meses, curso lectivo. --}}
<div x-data="filtroPeriodo('{{ $desde->toDateString() }}', '{{ $hasta->toDateString() }}')" class="flex flex-wrap items-end gap-3">
    <div>
        <x-input-label for="desde" value="Desde" />
        <x-text-input id="desde" name="desde" type="date" x-model="desde" class="mt-1 block" required />
    </div>
    <div>
        <x-input-label for="hasta" value="Hasta" />
        <x-text-input id="hasta" name="hasta" type="date" x-model="hasta" class="mt-1 block" required />
    </div>
    <div class="flex flex-wrap gap-1 pb-1 print:hidden">
        @foreach (['hoy' => 'Hoy', 'semana' => 'Esta semana', 'mes' => 'Este mes', 'trimestre' => 'Últimos 3 meses', 'curso' => 'Curso lectivo'] as $tipo => $texto)
            <button type="button" @click="rango('{{ $tipo }}')" class="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-ctp-50 hover:border-ctp-400">{{ $texto }}</button>
        @endforeach
    </div>
</div>
