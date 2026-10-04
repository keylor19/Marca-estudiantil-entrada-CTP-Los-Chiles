@props(['titulo' => null])

<section {{ $attributes->merge(['class' => 'bg-white shadow-sm rounded-lg print:shadow-none print:border']) }}>
    @if ($titulo)
        <h3 class="px-5 pt-4 font-semibold text-slate-900">{{ $titulo }}</h3>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</section>
