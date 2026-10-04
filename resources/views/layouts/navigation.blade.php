@php
    $usuario = Auth::user();
    $enlaces = collect([
        ['ruta' => 'dashboard', 'activo' => 'dashboard', 'texto' => 'Inicio', 'visible' => ! $usuario->esGuarda()],
        ['ruta' => 'escaner.index', 'activo' => 'escaner.*', 'texto' => 'Control de ingreso', 'visible' => $usuario->tieneRol(\App\Enums\Rol::Admin, \App\Enums\Rol::Guarda)],
        ['ruta' => 'estudiantes.index', 'activo' => 'estudiantes.*', 'texto' => 'Estudiantes', 'visible' => ! $usuario->esGuarda()],
        ['ruta' => 'carnets.index', 'activo' => 'carnets.*', 'texto' => 'Carnets', 'visible' => $usuario->esAdmin()],
        ['ruta' => 'registros.index', 'activo' => 'registros.*', 'texto' => 'Marcas', 'visible' => ! $usuario->esGuarda()],
        ['ruta' => 'reportes.index', 'activo' => 'reportes.*', 'texto' => 'Informes', 'visible' => ! $usuario->esGuarda()],
        ['ruta' => 'secciones.index', 'activo' => 'secciones.*', 'texto' => 'Secciones', 'visible' => $usuario->esAdmin()],
        ['ruta' => 'usuarios.index', 'activo' => 'usuarios.*', 'texto' => 'Usuarios', 'visible' => $usuario->esAdmin()],
        ['ruta' => 'configuracion.edit', 'activo' => 'configuracion.*', 'texto' => 'Configuración', 'visible' => $usuario->esAdmin()],
    ])->where('visible', true);
@endphp

<nav x-data="{ open: false }" class="bg-ctp-900 text-white border-b-4 border-celeste-400 print:hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                    <x-application-logo class="h-12 w-12" />
                    <span class="font-bold leading-tight text-sm">CTP Los Chiles<br><span class="font-normal text-ctp-200 text-xs">Control de asistencia</span></span>
                </a>

                <div class="hidden lg:flex items-center gap-1">
                    @foreach ($enlaces as $enlace)
                        <a href="{{ route($enlace['ruta']) }}"
                           @class([
                               'px-3 py-2 rounded-md text-sm font-medium transition',
                               'bg-ctp-700 text-white' => request()->routeIs($enlace['activo']),
                               'text-ctp-100 hover:bg-ctp-800' => ! request()->routeIs($enlace['activo']),
                           ])>{{ $enlace['texto'] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="hidden lg:flex lg:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-ctp-100 hover:text-white focus:outline-none transition">
                            <div class="text-right leading-tight">
                                {{ $usuario->name }}<br>
                                <span class="text-xs text-ctp-300">{{ $usuario->rol->etiqueta() }}</span>
                            </div>
                            <svg class="ms-1 fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Cambiar contraseña</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="p-2 rounded-md text-ctp-200 hover:bg-ctp-800 focus:outline-none" aria-label="Menú">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden border-t border-ctp-800">
        <div class="py-2 space-y-1 px-2">
            @foreach ($enlaces as $enlace)
                <a href="{{ route($enlace['ruta']) }}"
                   @class([
                       'block px-3 py-2 rounded-md text-base font-medium',
                       'bg-ctp-700' => request()->routeIs($enlace['activo']),
                       'text-ctp-100 hover:bg-ctp-800' => ! request()->routeIs($enlace['activo']),
                   ])>{{ $enlace['texto'] }}</a>
            @endforeach
        </div>
        <div class="py-3 border-t border-ctp-800 px-4">
            <div class="font-medium">{{ $usuario->name }}</div>
            <div class="text-sm text-ctp-300">{{ $usuario->rol->etiqueta() }}</div>
            <div class="mt-2 space-y-1">
                <a href="{{ route('profile.edit') }}" class="block py-1 text-ctp-100">Cambiar contraseña</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="py-1 text-ctp-100">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</nav>
