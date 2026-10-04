<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="usuario" value="Usuario" />
            <x-text-input id="usuario" class="block mt-1 w-full" type="text" name="usuario" :value="old('usuario')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('usuario')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-ctp-700 shadow-sm focus:ring-ctp-600" name="remember">
            <span class="ms-2 text-sm text-slate-600">Mantener la sesión iniciada</span>
        </label>

        <x-primary-button class="w-full py-3">Ingresar</x-primary-button>
    </form>
</x-guest-layout>
