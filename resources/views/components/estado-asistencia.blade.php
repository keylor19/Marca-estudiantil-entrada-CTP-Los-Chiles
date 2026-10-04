@props(['asistencia' => null])

{{-- El estado siempre lleva texto e ícono, nunca solo color. --}}
@if (! $asistencia)
    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-900">✕ Ausente</span>
@elseif ($asistencia->estado === \App\Enums\EstadoAsistencia::Tarde)
    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900">⏱ Tardía</span>
@else
    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-900">✓ Presente</span>
@endif
