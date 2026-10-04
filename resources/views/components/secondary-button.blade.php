<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-sm text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-ctp-600 focus:ring-offset-2 disabled:opacity-25 transition']) }}>
    {{ $slot }}
</button>
