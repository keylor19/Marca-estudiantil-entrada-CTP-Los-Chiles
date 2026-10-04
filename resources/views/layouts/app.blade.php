<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($titulo) ? $titulo.' · ' : '' }}{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800">
        <div class="min-h-screen bg-slate-100 print:bg-white">
            {{-- Encabezado oficial (MEP / Regional / Circuito / CTP) solo en los documentos impresos. --}}
            <img src="{{ asset('img/encabezado-mep.jpg') }}" alt="" class="hidden print:block w-full mb-2">

            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white shadow-sm print:shadow-none">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-3">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
                <x-mensajes />

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
