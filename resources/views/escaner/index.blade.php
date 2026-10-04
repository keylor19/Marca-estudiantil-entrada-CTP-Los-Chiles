<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
        <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Control de ingreso · {{ $configuracion->nombre_colegio }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-ctp-950 text-white">
<div x-data="escaner({
        url: '{{ route('escaner.registrar') }}',
        segundos: {{ $configuracion->segundos_pantalla }},
        ultimas: {{ Js::from($ultimas) }},
        totalHoy: {{ $totalHoy }},
        registrarSalida: {{ Js::from($configuracion->registrar_salida) }},
    })" class="min-h-screen flex flex-col">

    {{-- Encabezado --}}
    <header class="bg-ctp-950 border-b-4 border-celeste-400 px-6 py-3 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <x-application-logo class="h-14 w-14" />
            <div>
                <div class="text-lg font-bold leading-tight">CONTROL DE INGRESO DE ESTUDIANTES</div>
                <div class="text-sm text-ctp-200">{{ $configuracion->nombre_colegio }} · {{ auth()->user()->name }}</div>
            </div>
        </div>

        <div class="text-right">
            <div class="text-3xl font-bold tabular-nums" x-text="reloj"></div>
            <div class="text-sm text-ctp-200" x-text="fecha"></div>
        </div>

        <div class="flex items-center gap-3">
            <template x-if="registrarSalida">
                <div class="flex rounded-lg overflow-hidden border border-ctp-700" role="group" aria-label="Tipo de marca">
                    <button type="button" @click="modo = 'entrada'" :class="modo === 'entrada' ? 'bg-emerald-600' : 'bg-transparent text-ctp-200'" class="px-4 py-2 font-semibold">ENTRADA</button>
                    <button type="button" @click="modo = 'salida'" :class="modo === 'salida' ? 'bg-orange-600' : 'bg-transparent text-ctp-200'" class="px-4 py-2 font-semibold">SALIDA</button>
                </div>
            </template>
            @unless (auth()->user()->esGuarda())
                <a href="{{ route('dashboard') }}" class="rounded-md border border-ctp-700 px-3 py-2 text-sm text-ctp-100 hover:bg-ctp-900">Volver al sistema</a>
            @endunless
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-md border border-ctp-700 px-3 py-2 text-sm text-ctp-100 hover:bg-ctp-900">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <div class="flex-1 grid lg:grid-cols-[1fr_22rem]">
        {{-- Resultado de la lectura --}}
        <main class="flex flex-col items-center justify-center p-6 transition-colors duration-200" :class="estilo">
            <form @submit.prevent="enviar" class="w-full max-w-xl mb-6">
                <label for="codigo" class="sr-only">Código del carnet</label>
                <input id="codigo" x-ref="campo" x-model="codigo" @input="alEscribir" type="text" autocomplete="off" autofocus
                       class="w-full rounded-lg border-0 bg-white/15 px-4 py-3 text-center text-2xl tracking-widest text-white placeholder-white/60 focus:ring-4 focus:ring-white/40"
                       placeholder="Pase el carnet por el lector o digite la cédula">
            </form>

            {{-- Esperando --}}
            <div x-show="!resultado" class="text-center">
                <svg class="mx-auto h-40 w-40 text-ctp-300 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
                    <path d="M3 5v14M6 5v14M9 5v14M12 5v14M15 5v14M18 5v14M21 5v14" stroke-linecap="round"/>
                </svg>
                <p class="mt-6 text-3xl font-bold" x-text="modo === 'salida' ? 'ESPERANDO LECTURA (SALIDA)…' : 'ESPERANDO LECTURA DEL CARNET…'"></p>
                <p class="mt-2 text-ctp-200">Si el estudiante no trae carnet, digite su número de cédula y presione ENTER.</p>
            </div>

            {{-- Tarjeta del estudiante --}}
            <div x-show="resultado" x-cloak class="w-full max-w-3xl text-center">
                <p class="text-4xl font-extrabold tracking-wide" x-text="resultado?.titulo"></p>

                <template x-if="resultado?.estudiante">
                    <div class="mt-6 flex flex-col sm:flex-row items-center gap-8 rounded-2xl bg-white p-6 text-left text-slate-900 shadow-2xl">
                        <img :src="resultado.estudiante.foto" alt="Foto del estudiante" class="h-64 w-52 rounded-xl object-cover bg-slate-200 shrink-0">
                        <div class="space-y-3">
                            <div class="text-3xl font-extrabold leading-tight" x-text="resultado.estudiante.nombre"></div>
                            <div class="text-xl"><span class="text-slate-500">Cédula:</span> <span class="font-semibold" x-text="resultado.estudiante.cedula"></span></div>
                            <div class="text-xl"><span class="text-slate-500">Sección:</span> <span class="font-semibold" x-text="resultado.estudiante.seccion"></span></div>
                            <div class="text-xl" x-show="resultado.hora_entrada"><span class="text-slate-500">Hora de entrada:</span> <span class="font-bold text-2xl tabular-nums" x-text="resultado.hora_entrada"></span></div>
                            <div class="text-xl" x-show="resultado.hora_salida"><span class="text-slate-500">Hora de salida:</span> <span class="font-bold text-2xl tabular-nums" x-text="resultado.hora_salida"></span></div>
                        </div>
                    </div>
                </template>

                <p class="mt-5 text-xl font-medium" x-text="resultado?.mensaje"></p>
            </div>
        </main>

        {{-- Últimas marcas --}}
        <aside class="bg-ctp-950 p-5 hidden lg:block">
            <div class="rounded-lg bg-white/10 p-4 text-center">
                <div class="text-sm text-ctp-200">Entradas registradas hoy</div>
                <div class="text-5xl font-extrabold tabular-nums" x-text="totalHoy"></div>
            </div>

            <h2 class="mt-6 mb-3 text-sm font-semibold uppercase tracking-wide text-ctp-200">Últimas marcas</h2>
            <ul class="space-y-2">
                <template x-for="(marca, i) in ultimas" :key="i + marca.nombre + marca.hora">
                    <li class="rounded-md bg-white/5 px-3 py-2">
                        <div class="font-semibold truncate" x-text="marca.nombre"></div>
                        <div class="flex justify-between text-sm text-ctp-200">
                            <span x-text="marca.seccion"></span>
                            <span><span x-text="marca.tipo"></span> · <span class="tabular-nums" x-text="marca.hora"></span></span>
                        </div>
                    </li>
                </template>
                <li x-show="ultimas.length === 0" class="text-sm text-ctp-300">Todavía no hay marcas hoy.</li>
            </ul>
        </aside>
    </div>
</div>
</body>
</html>
