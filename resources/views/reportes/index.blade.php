<x-app-layout titulo="Informes">
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-900">Informes de asistencia</h2>
            <p class="text-sm text-slate-500">Todos los informes aceptan cualquier período: un día, una semana, un mes, tres meses, el curso lectivo o un rango personalizado.</p>
        </div>
    </x-slot>

    @php($informes = [
        ['ruta' => 'reportes.estudiante', 'titulo' => 'Por estudiante', 'texto' => 'Busque por número de cédula y vea día por día sus entradas, tardías y ausencias.'],
        ['ruta' => 'reportes.grupo', 'titulo' => 'Por sección', 'texto' => 'Totales de asistencias, tardías, ausencias y porcentaje de cada estudiante de una sección, un subgrupo o todo el colegio.'],
        ['ruta' => 'reportes.diario', 'titulo' => 'Lista del día', 'texto' => 'Quién llegó, a qué hora, quién llegó tarde y quién está ausente en una fecha.'],
        ['ruta' => 'reportes.secciones', 'titulo' => 'Comparativo de secciones', 'texto' => 'Porcentaje de asistencia, tardías y ausencias de todas las secciones en el período.'],
        ['ruta' => 'registros.index', 'titulo' => 'Registro de marcas', 'texto' => 'Cada marca con hora y usuario que la registró (auditoría).'],
    ])

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($informes as $informe)
            <a href="{{ route($informe['ruta']) }}" class="block bg-white rounded-lg shadow-sm p-5 hover:ring-2 hover:ring-ctp-600 transition">
                <h3 class="font-semibold text-ctp-900">{{ $informe['titulo'] }} →</h3>
                <p class="mt-2 text-sm text-slate-600">{{ $informe['texto'] }}</p>
            </a>
        @endforeach
    </div>

    <p class="text-sm text-slate-500">Todos se pueden imprimir (Ctrl + P) o descargar en Excel.</p>
</x-app-layout>
