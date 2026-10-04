<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 bg-ctp-800 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-ctp-700 focus:outline-none focus:ring-2 focus:ring-ctp-600 focus:ring-offset-2 disabled:opacity-50 transition']) }}>
    {{ $slot }}
</button>
