<x-app-layout titulo="Cambiar contraseña">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Mi cuenta</h2>
    </x-slot>

    <x-tarjeta class="max-w-xl">
        <p class="text-sm text-slate-600 mb-4">{{ $user->name }} · usuario <span class="font-mono">{{ $user->usuario }}</span> · {{ $user->rol->etiqueta() }}</p>
        @include('profile.partials.update-password-form')
    </x-tarjeta>
</x-app-layout>
