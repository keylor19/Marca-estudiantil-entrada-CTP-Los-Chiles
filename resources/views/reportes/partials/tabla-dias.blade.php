<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="text-left text-slate-600 border-b">
            <tr>
                <th class="py-2 pe-4 font-semibold">Fecha</th>
                <th class="py-2 pe-4 font-semibold">Día</th>
                <th class="py-2 pe-4 font-semibold">Estado</th>
                <th class="py-2 pe-4 font-semibold">Entrada</th>
                <th class="py-2 pe-4 font-semibold">Salida</th>
                <th class="py-2 font-semibold">Registrado por</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($dias as $dia)
                <tr>
                    <td class="py-1.5 pe-4 tabular-nums">{{ $dia['fecha']->format('d/m/Y') }}</td>
                    <td class="py-1.5 pe-4 capitalize">{{ $dia['fecha']->translatedFormat('l') }}</td>
                    <td class="py-1.5 pe-4">
                        <x-estado-asistencia :asistencia="$dia['asistencia']" />
                        @unless ($dia['lectivo'])
                            <span class="text-xs text-slate-500">(día no lectivo)</span>
                        @endunless
                    </td>
                    <td class="py-1.5 pe-4 tabular-nums">{{ $dia['asistencia'] ? \App\Models\Asistencia::formatoHora($dia['asistencia']->hora_entrada) : '--' }}</td>
                    <td class="py-1.5 pe-4 tabular-nums">{{ \App\Models\Asistencia::formatoHora($dia['asistencia']?->hora_salida) }}</td>
                    <td class="py-1.5 text-slate-600">{{ $dia['asistencia']?->usuario?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-4 text-center text-slate-500">No hay días lectivos en el período.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
