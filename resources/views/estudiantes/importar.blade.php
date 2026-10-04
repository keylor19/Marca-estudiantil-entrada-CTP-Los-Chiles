<x-app-layout titulo="Importar estudiantes">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Importar estudiantes desde Excel</h2>
        <x-boton-enlace variante="secundario" :href="route('estudiantes.index')">Volver</x-boton-enlace>
    </x-slot>

    @if ($resultado = session('resultado'))
        <x-tarjeta titulo="Resultado de la importación">
            <p class="text-sm">
                <span class="font-semibold text-emerald-800">{{ $resultado['creados'] }}</span> estudiantes nuevos ·
                <span class="font-semibold text-ctp-800">{{ $resultado['actualizados'] }}</span> actualizados ·
                <span class="font-semibold text-red-800">{{ count($resultado['errores']) }}</span> filas con error
            </p>
            @if ($resultado['errores'])
                <ul class="mt-3 max-h-64 overflow-y-auto list-disc ms-5 text-sm text-red-800">
                    @foreach ($resultado['errores'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            @if (session('anio_matricula'))
                <p class="mt-3 text-sm text-slate-700">Los estudiantes del archivo quedaron matriculados en <strong>{{ session('anio_matricula') }}</strong>.</p>
            @endif
            @if (session('sin_matricula'))
                <div class="mt-3 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                    ⚠ Hay <strong>{{ session('sin_matricula') }}</strong> estudiante(s) activo(s) que no están en la matrícula {{ \App\Models\Estudiante::cursoLectivo() }}.
                    <a href="{{ route('estudiantes.index', ['estado' => 'vencidos']) }}" class="underline font-semibold">Revíselos</a> para actualizar sus datos o deshabilitarlos.
                </div>
            @endif
            @if ($resultado['creados'] > 0)
                <p class="mt-3 text-sm text-slate-600">A cada estudiante nuevo se le generó su código de barras. Ahora puede <a href="{{ route('estudiantes.fotos') }}" class="text-ctp-800 underline">cargar las fotos</a> e <a href="{{ route('carnets.index') }}" class="text-ctp-800 underline">imprimir los carnets</a>.</p>
            @endif
        </x-tarjeta>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        <x-tarjeta titulo="1. Prepare el archivo">
            <div class="text-sm text-slate-700 space-y-3">
                <p>El archivo puede ser <strong>Excel (.xlsx)</strong> o <strong>CSV</strong>. La primera fila debe tener estos encabezados (en cualquier orden):</p>
                <table class="text-sm border">
                    <tbody class="divide-y">
                        <tr><td class="px-3 py-1 font-mono">cedula</td><td class="px-3 py-1">Obligatorio. Se usa para no duplicar: si la cédula ya existe, se actualizan los datos.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">nombre</td><td class="px-3 py-1">Obligatorio.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">apellido1</td><td class="px-3 py-1">Obligatorio.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">apellido2</td><td class="px-3 py-1">Opcional.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">seccion</td><td class="px-3 py-1">Obligatorio, ej. <em>10-2</em>. Si no existe, se crea.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">subgrupo</td><td class="px-3 py-1">Opcional, ej. <em>A</em> o <em>B</em> (si se deja vacío se usa A).</td></tr>
                        <tr><td class="px-3 py-1 font-mono">especialidad</td><td class="px-3 py-1">Opcional.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">fecha_nacimiento</td><td class="px-3 py-1">Opcional, formato dd/mm/aaaa.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">encargado</td><td class="px-3 py-1">Opcional. Nombre del encargado legal.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">parentesco</td><td class="px-3 py-1">Opcional, ej. <em>Madre</em>.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">telefono_encargado</td><td class="px-3 py-1">Opcional. Número de WhatsApp, ej. <em>8888-7777</em>.</td></tr>
                        <tr><td class="px-3 py-1 font-mono">correo_encargado</td><td class="px-3 py-1">Opcional.</td></tr>
                    </tbody>
                </table>
                <x-boton-enlace variante="excel" :href="route('estudiantes.plantilla')">Descargar plantilla de Excel</x-boton-enlace>
            </div>
        </x-tarjeta>

        <x-tarjeta titulo="2. Suba el archivo">
            <form method="POST" action="{{ route('estudiantes.importar.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="anio_matricula" value="Año de la matrícula" />
                    <x-select id="anio_matricula" name="anio_matricula" class="mt-1 block">
                        @foreach ($anios as $anio)
                            <option value="{{ $anio }}" @selected(old('anio_matricula', $anios[0]) == $anio)>{{ $anio }}</option>
                        @endforeach
                    </x-select>
                    <p class="mt-1 text-xs text-slate-500">Cada año suba la matrícula completa. Los estudiantes se buscan por cédula: se actualizan sus datos y quedan matriculados en este año. Los que no vengan en el archivo quedan con la matrícula vencida.</p>
                    <x-input-error :messages="$errors->get('anio_matricula')" class="mt-1" />
                </div>
                <input type="file" name="archivo" accept=".xlsx,.csv" required
                       class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-ctp-50 file:px-3 file:py-2 file:text-ctp-800">
                <x-input-error :messages="$errors->get('archivo')" />
                <x-primary-button>Importar estudiantes</x-primary-button>
            </form>
        </x-tarjeta>
    </div>
</x-app-layout>
