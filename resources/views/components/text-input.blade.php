@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-ctp-600 focus:ring-ctp-600 rounded-md shadow-sm']) }}>
