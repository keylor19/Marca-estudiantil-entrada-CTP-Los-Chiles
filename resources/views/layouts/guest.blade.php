<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Iniciar sesión · {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 bg-gradient-to-b from-ctp-950 to-ctp-800">
            <div class="flex flex-col items-center text-white text-center">
                <x-application-logo class="w-32 h-32 drop-shadow-lg" />
                <h1 class="mt-3 text-2xl font-bold">CTP Los Chiles</h1>
                <p class="text-ctp-200">Control de asistencia estudiantil</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-xl overflow-hidden rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
