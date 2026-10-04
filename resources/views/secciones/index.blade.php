<x-app-layout titulo="Secciones">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Secciones y subgrupos</h2>
        <x-boton-enlace :href="route('secciones.create')">+ Nueva sección</x-boton-enlace>
    </x-slot>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Sección</th>
                    <th class="px-4 py-3 font-semibold">Nivel</th>
                    <th class="px-4 py-3 font-semibold">Subgrupos (estudiantes)</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($secciones as $seccion)
                    <tr>
                        <td class="px-4 py-2 font-semibold">{{ $seccion->nombre }}</td>
                        <td class="px-4 py-2">{{ $seccion->nivel }}</td>
                        <td class="px-4 py-2">
                            @foreach ($seccion->subgrupos as $subgrupo)
                                <span class="inline-block me-3">
                                    <strong>{{ $subgrupo->nombre }}</strong>
                                    {{ $subgrupo->especialidad ? '· '.$subgrupo->especialidad : '' }}
                                    <span class="text-slate-500">({{ $subgrupo->estudiantes_count }})</span>
                                </span>
                            @endforeach
                        </td>
                        <td class="px-4 py-2">{{ $seccion->activo ? '● Activa' : '○ Inactiva' }}</td>
                        <td class="px-4 py-2 text-right"><a href="{{ route('secciones.edit', $seccion) }}" class="text-ctp-800 hover:underline">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No hay secciones. Créelas aquí o impórtelas junto con los estudiantes desde Excel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
