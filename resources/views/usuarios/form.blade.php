<x-app-layout :titulo="$usuario->exists ? 'Editar usuario' : 'Nuevo usuario'">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
        <x-boton-enlace variante="secundario" :href="route('usuarios.index')">Volver</x-boton-enlace>
    </x-slot>

    <x-tarjeta class="max-w-2xl">
        <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="grid sm:grid-cols-2 gap-4">
            @csrf
            @if ($usuario->exists)
                @method('PUT')
            @endif

            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nombre completo *" />
                <x-text-input id="name" name="name" :value="old('name', $usuario->name)" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="usuario" value="Usuario (para iniciar sesión) *" />
                <x-text-input id="usuario" name="usuario" :value="old('usuario', $usuario->usuario)" class="mt-1 block w-full" required placeholder="guarda01" />
            </div>
            <div>
                <x-input-label for="email" value="Correo (opcional)" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $usuario->email)" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="rol" value="Rol *" />
                <x-select id="rol" name="rol" class="mt-1 block w-full" :disabled="$usuario->is(auth()->user())">
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->value }}" @selected(old('rol', $usuario->rol?->value) === $rol->value)>{{ $rol->etiqueta() }}</option>
                    @endforeach
                </x-select>
                @if ($usuario->is(auth()->user()))
                    <input type="hidden" name="rol" value="{{ $usuario->rol->value }}">
                @endif
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-ctp-700" @checked(old('activo', $usuario->activo))>
                    <span class="text-sm">Usuario activo</span>
                </label>
            </div>
            <div>
                <x-input-label for="password" :value="$usuario->exists ? 'Nueva contraseña (vacío = no cambiar)' : 'Contraseña *'" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $usuario->exists" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            </div>
            <p class="sm:col-span-2 text-xs text-slate-500">Mínimo 8 caracteres.</p>
            <div class="sm:col-span-2">
                <x-primary-button>Guardar</x-primary-button>
            </div>
        </form>
    </x-tarjeta>
</x-app-layout>
