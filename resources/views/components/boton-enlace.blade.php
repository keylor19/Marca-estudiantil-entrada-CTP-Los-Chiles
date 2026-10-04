@props(['variante' => 'primario'])

<a {{ $attributes->class([
    'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md font-semibold text-sm transition focus:outline-none focus:ring-2 focus:ring-ctp-600 focus:ring-offset-2 print:hidden',
    'bg-ctp-800 text-white hover:bg-ctp-700' => $variante === 'primario',
    'bg-white border border-slate-300 text-slate-700 shadow-sm hover:bg-slate-50' => $variante === 'secundario',
    'bg-emerald-700 text-white hover:bg-emerald-600' => $variante === 'excel',
]) }}>{{ $slot }}</a>
