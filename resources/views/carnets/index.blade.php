<x-app-layout titulo="Carnets">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Impresión de carnets</h2>
    </x-slot>

    {{-- La cantidad de carnets por hoja se aplica a los formularios y a los enlaces de la tabla. --}}
    <div x-data="{ porHoja: '9' }" class="space-y-6">
    <div class="grid lg:grid-cols-2 gap-6">
        <x-tarjeta titulo="Por sección o subgrupo">
            <div class="mb-4">
                <x-input-label for="por_hoja" value="Carnets por hoja" />
                <x-select id="por_hoja" x-model="porHoja" class="mt-1 block">
                    @foreach ($distribuciones as $cantidad)
                        <option value="{{ $cantidad }}">{{ $cantidad }} {{ $cantidad === 1 ? 'carnet' : 'carnets' }} por hoja</option>
                    @endforeach
                </x-select>
            </div>
            <form method="GET" action="{{ route('carnets.imprimir') }}" target="_blank" class="space-y-4">
                <input type="hidden" name="por_hoja" :value="porHoja">
                <x-selector-grupo :secciones="$secciones" todos="Seleccione una sección…" />
                <div class="flex flex-wrap gap-2">
                    <x-primary-button>Generar carnets</x-primary-button>
                </div>
            </form>
            <form method="GET" action="{{ route('carnets.imprimir') }}" target="_blank" class="mt-4 border-t pt-4">
                <input type="hidden" name="todos" value="1">
                <input type="hidden" name="por_hoja" :value="porHoja">
                <button class="text-sm text-ctp-800 hover:underline">Generar los carnets de todos los estudiantes activos</button>
            </form>
        </x-tarjeta>

        <x-tarjeta titulo="Cómo funciona">
            <ul class="list-disc ms-5 text-sm text-slate-700 space-y-2">
                <li>Cada carnet lleva foto, nombre completo, cédula, sección y un <strong>código de barras Code 128</strong> único (ej. <span class="font-mono">EST000125</span>) que leen los lectores USB comunes.</li>
                <li>El código de barras solo contiene el identificador; los datos del estudiante se consultan en el sistema.</li>
                <li>Se imprimen de 1 a 9 carnets por hoja carta (9 por defecto), tamaño tarjeta (54 × 85,6 mm), listos para recortar y enmicar. La cantidad también se puede cambiar en la hoja de impresión.</li>
                <li>Para reimprimir un solo carnet, abra la ficha del estudiante y use «Imprimir carnet».</li>
            </ul>
        </x-tarjeta>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Sección</th>
                    <th class="px-4 py-3 font-semibold">Nivel</th>
                    <th class="px-4 py-3 font-semibold">Estudiantes</th>
                    <th class="px-4 py-3 font-semibold">Subgrupos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($secciones as $seccion)
                    <tr>
                        <td class="px-4 py-2 font-semibold">
                            <a href="{{ route('carnets.imprimir', ['seccion_id' => $seccion->id]) }}" :href="'{{ route('carnets.imprimir', ['seccion_id' => $seccion->id]) }}&por_hoja=' + porHoja" target="_blank" class="text-ctp-800 hover:underline">{{ $seccion->nombre }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $seccion->nivel }}</td>
                        <td class="px-4 py-2 tabular-nums">{{ $seccion->estudiantes_count }}</td>
                        <td class="px-4 py-2 space-x-3">
                            @foreach ($seccion->subgrupos as $subgrupo)
                                <a href="{{ route('carnets.imprimir', ['subgrupo_id' => $subgrupo->id]) }}" :href="'{{ route('carnets.imprimir', ['subgrupo_id' => $subgrupo->id]) }}&por_hoja=' + porHoja" target="_blank" class="text-ctp-800 hover:underline">{{ trim($seccion->nombre.' '.$subgrupo->nombre) }}</a>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No hay secciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</x-app-layout>
