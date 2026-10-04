<x-app-layout :titulo="$estudiante->nombreCompleto()">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">{{ $estudiante->nombreCompleto() }}</h2>
        <div class="flex flex-wrap gap-2">
            <x-boton-enlace variante="secundario" :href="route('reportes.estudiante', ['cedula' => $estudiante->cedula])">Informe de asistencia</x-boton-enlace>
            @if (auth()->user()->esAdmin())
                <x-boton-enlace variante="secundario" :href="route('carnets.imprimir', ['ids' => [$estudiante->id]])" target="_blank">Imprimir carnet</x-boton-enlace>
                <x-boton-enlace :href="route('estudiantes.edit', $estudiante)">Editar</x-boton-enlace>
            @endif
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-[16rem_1fr] gap-6">
        <x-tarjeta>
            <img src="{{ $estudiante->fotoUrl() }}" alt="Foto de {{ $estudiante->nombreCompleto() }}" class="w-full aspect-[3/4] object-cover rounded-md bg-slate-200">
            <dl class="mt-4 space-y-2 text-sm">
                <div><dt class="text-slate-500">Cédula</dt><dd class="font-semibold">{{ $estudiante->cedula }}</dd></div>
                <div><dt class="text-slate-500">Sección</dt><dd class="font-semibold">{{ $estudiante->seccionNombre() }} <span class="font-normal text-slate-500">({{ $estudiante->subgrupo->seccion->nivel }})</span></dd></div>
                @if ($estudiante->subgrupo->especialidad)
                    <div><dt class="text-slate-500">Especialidad</dt><dd class="font-semibold">{{ $estudiante->subgrupo->especialidad }}</dd></div>
                @endif
                @if ($estudiante->fecha_nacimiento)
                    <div><dt class="text-slate-500">Fecha de nacimiento</dt><dd class="font-semibold">{{ $estudiante->fecha_nacimiento->format('d/m/Y') }}</dd></div>
                @endif
                <div><dt class="text-slate-500">Código de barras</dt><dd class="font-mono font-semibold">{{ $estudiante->codigo_barra }}</dd></div>
                <div><dt class="text-slate-500">Año de matrícula</dt><dd class="font-semibold tabular-nums">{{ $estudiante->anio_matricula ?? 'Sin registrar' }}</dd></div>
                <div><dt class="text-slate-500">Estado</dt><dd class="font-semibold">{{ $estudiante->activo ? '● Activo' : '○ Inactivo' }}</dd></div>
            </dl>

            @if ($estudiante->activo && ! $estudiante->matriculaVigente())
                <div class="mt-4 rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-900">
                    ⚠ No está en la matrícula {{ \App\Models\Estudiante::cursoLectivo() }}, por lo que no puede marcar la entrada.
                    @if (auth()->user()->esAdmin())
                        <a href="{{ route('estudiantes.edit', $estudiante) }}" class="underline font-semibold">Actualice sus datos</a> o deshabilítelo.
                    @endif
                </div>
            @endif

            @if (auth()->user()->esAdmin())
                <form method="POST" action="{{ route('estudiantes.destroy', $estudiante) }}" class="mt-6"
                      onsubmit="return confirm('¿Eliminar este estudiante? Si ya tiene asistencias solo se desactivará.')">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm text-red-700 hover:underline">Eliminar estudiante</button>
                </form>
            @endif
        </x-tarjeta>

        <div class="space-y-6">
            @php($resumen = $reporte['resumen'])
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-dato etiqueta="Días lectivos (30 días)" :valor="$resumen['lectivos']" />
                <x-dato etiqueta="✓ Asistencias" :valor="$resumen['presentes']" :detalle="$resumen['porcentaje'].'% de asistencia'" />
                <x-dato etiqueta="⏱ Tardías" :valor="$resumen['tardias']" />
                <x-dato etiqueta="✕ Ausencias" :valor="$resumen['ausencias']" />
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <x-tarjeta titulo="Encargado legal">
                    @if ($estudiante->encargado_nombre || $estudiante->encargado_telefono || $estudiante->encargado_correo)
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-slate-500">Nombre</dt><dd class="font-semibold">{{ $estudiante->encargado_nombre ?: '—' }} @if ($estudiante->encargado_parentesco)<span class="font-normal text-slate-500">({{ $estudiante->encargado_parentesco }})</span>@endif</dd></div>
                            <div><dt class="text-slate-500">Teléfono / WhatsApp</dt><dd class="font-semibold">{{ $estudiante->encargado_telefono ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">Correo</dt><dd class="font-semibold break-all">{{ $estudiante->encargado_correo ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">Avisos de entrada y salida</dt><dd class="font-semibold">{{ $estudiante->avisar_encargado ? '● Activados' : '○ Desactivados' }}</dd></div>
                        </dl>
                    @else
                        <p class="text-sm text-slate-500">No se han registrado los datos del encargado.</p>
                    @endif
                </x-tarjeta>

                <x-tarjeta titulo="Últimos avisos enviados">
                    <ul class="divide-y divide-slate-100 text-sm">
                        @forelse ($avisos as $aviso)
                            <li class="py-1.5">
                                <div class="flex justify-between gap-2">
                                    <span>{{ $aviso->created_at->format('d/m/Y h:i A') }} · {{ ucfirst($aviso->tipo) }} · {{ $aviso->canal === 'whatsapp' ? 'WhatsApp' : 'Correo' }}</span>
                                    @if ($aviso->estado === \App\Models\Aviso::ENVIADO)
                                        <span class="text-emerald-800 font-semibold">✓ Enviado</span>
                                    @else
                                        <span class="text-red-800 font-semibold">✕ Falló</span>
                                    @endif
                                </div>
                                @if ($aviso->error)
                                    <div class="text-xs text-red-700">{{ $aviso->error }}</div>
                                @endif
                            </li>
                        @empty
                            <li class="py-1.5 text-slate-500">Todavía no se han enviado avisos.</li>
                        @endforelse
                    </ul>
                </x-tarjeta>
            </div>

            <x-tarjeta titulo="Últimos 30 días">
                @include('reportes.partials.tabla-dias', ['dias' => array_reverse($reporte['dias'])])
            </x-tarjeta>
        </div>
    </div>
</x-app-layout>
