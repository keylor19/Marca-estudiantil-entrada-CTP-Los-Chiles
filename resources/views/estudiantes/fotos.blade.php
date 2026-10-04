<x-app-layout titulo="Cargar fotos">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Carga masiva de fotografías</h2>
        <x-boton-enlace variante="secundario" :href="route('estudiantes.index')">Volver</x-boton-enlace>
    </x-slot>

    @if ($resultado = session('resultado'))
        <x-tarjeta titulo="Resultado">
            <p class="text-sm"><span class="font-semibold text-emerald-800">{{ $resultado['asignadas'] }}</span> fotos asignadas.</p>
            @if ($resultado['sin_estudiante'])
                <p class="mt-2 text-sm font-semibold text-red-800">{{ count($resultado['sin_estudiante']) }} archivos no coinciden con ninguna cédula:</p>
                <ul class="mt-1 max-h-48 overflow-y-auto list-disc ms-5 text-sm text-red-800">
                    @foreach ($resultado['sin_estudiante'] as $archivo)
                        <li>{{ $archivo }}</li>
                    @endforeach
                </ul>
            @endif
        </x-tarjeta>
    @endif

    <x-tarjeta>
        <div class="text-sm text-slate-700 space-y-2 mb-6">
            <p>Nombre cada foto con la <strong>cédula del estudiante</strong> (con o sin guiones), por ejemplo <span class="font-mono">2-0876-0123.jpg</span> o <span class="font-mono">208760123.jpg</span>. También se acepta el código del carnet (<span class="font-mono">EST000125.jpg</span>).</p>
            <p>Para muchas fotos, comprímalas en un archivo <strong>.zip</strong> y súbalo de una sola vez. Las fotos se reducen automáticamente al tamaño del carnet.</p>
        </div>

        <form method="POST" action="{{ route('estudiantes.fotos.store') }}" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-6">
            @csrf
            <div>
                <x-input-label value="Archivo .zip con fotos" />
                <input type="file" name="zip" accept=".zip"
                       class="mt-1 block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-ctp-50 file:px-3 file:py-2 file:text-ctp-800">
            </div>
            <div>
                <x-input-label value="O seleccione varias fotos (máx. 20 a la vez)" />
                <input type="file" name="fotos[]" multiple accept="image/jpeg,image/png,image/webp"
                       class="mt-1 block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-ctp-50 file:px-3 file:py-2 file:text-ctp-800">
            </div>
            <div class="md:col-span-2">
                <x-primary-button>Subir fotos</x-primary-button>
            </div>
        </form>
    </x-tarjeta>
</x-app-layout>
