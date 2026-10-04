@props(['etiqueta', 'valor', 'detalle' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-sm p-4 print:shadow-none print:border']) }}>
    <div class="text-sm font-medium text-slate-500">{{ $etiqueta }}</div>
    <div class="mt-1 text-3xl font-bold text-slate-900 tabular-nums">{{ $valor }}</div>
    @if ($detalle)
        <div class="mt-1 text-xs text-slate-500">{{ $detalle }}</div>
    @endif
</div>
