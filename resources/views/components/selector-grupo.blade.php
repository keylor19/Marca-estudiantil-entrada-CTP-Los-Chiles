@props(['secciones', 'todos' => 'Todo el colegio'])

@php
    $subgrupos = $secciones->mapWithKeys(fn ($seccion) => [
        $seccion->id => $seccion->subgrupos->map(fn ($subgrupo) => [
            'id' => $subgrupo->id,
            'nombre' => trim($seccion->nombre.' '.$subgrupo->nombre).($subgrupo->especialidad ? ' · '.$subgrupo->especialidad : ''),
        ])->values(),
    ]);
@endphp

{{-- Sección y subgrupo dependientes (el subgrupo es opcional). --}}
<div x-data="{ seccion: '{{ request('seccion_id') }}', subgrupo: '{{ request('subgrupo_id') }}', subgrupos: {{ Js::from($subgrupos) }} }"
     class="flex flex-wrap items-end gap-3">
    <div>
        <x-input-label for="seccion_id" value="Sección" />
        <x-select id="seccion_id" name="seccion_id" x-model="seccion" @change="subgrupo = ''" class="mt-1 block">
            <option value="">{{ $todos }}</option>
            @foreach ($secciones as $seccion)
                <option value="{{ $seccion->id }}">{{ $seccion->nombre }}</option>
            @endforeach
        </x-select>
    </div>
    <div x-show="seccion && (subgrupos[seccion] || []).length > 0" x-cloak>
        <x-input-label for="subgrupo_id" value="Subgrupo" />
        <x-select id="subgrupo_id" name="subgrupo_id" x-model="subgrupo" class="mt-1 block">
            <option value="">Toda la sección</option>
            <template x-for="g in (subgrupos[seccion] || [])" :key="g.id">
                <option :value="g.id" x-text="g.nombre" :selected="String(g.id) === subgrupo"></option>
            </template>
        </x-select>
    </div>
</div>
