<x-app-layout titulo="Configuración">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">Configuración</h2>
    </x-slot>

    <div class="grid lg:grid-cols-2 gap-6">
        <x-tarjeta titulo="Colegio y horario">
            <form method="POST" action="{{ route('configuracion.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="nombre_colegio" value="Nombre del colegio (aparece en el carnet)" />
                    <x-text-input id="nombre_colegio" name="nombre_colegio" :value="old('nombre_colegio', $configuracion->nombre_colegio)" class="mt-1 block w-full" required />
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="hora_entrada" value="Hora de entrada" />
                        <x-text-input id="hora_entrada" name="hora_entrada" type="time" :value="old('hora_entrada', substr($configuracion->hora_entrada, 0, 5))" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="minutos_tolerancia" value="Minutos de tolerancia" />
                        <x-text-input id="minutos_tolerancia" name="minutos_tolerancia" type="number" min="0" max="240" :value="old('minutos_tolerancia', $configuracion->minutos_tolerancia)" class="mt-1 block w-full" required />
                    </div>
                </div>
                <p class="text-sm text-slate-600">Una entrada después de las <strong>{{ \App\Models\Asistencia::formatoHora($configuracion->horaLimite()) }}</strong> se registra como <strong>llegada tardía</strong>.</p>

                <div>
                    <x-input-label value="Días lectivos" />
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'] as $numero => $dia)
                            <label class="flex items-center gap-1.5 text-sm">
                                <input type="checkbox" name="dias_lectivos[]" value="{{ $numero }}" class="rounded border-slate-300 text-ctp-700" @checked(in_array($numero, old('dias_lectivos', $configuracion->dias_lectivos)))>
                                {{ $dia }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="flex items-center gap-2">
                    <input type="hidden" name="registrar_salida" value="0">
                    <input type="checkbox" name="registrar_salida" value="1" class="rounded border-slate-300 text-ctp-700" @checked(old('registrar_salida', $configuracion->registrar_salida))>
                    <span class="text-sm">Registrar también la <strong>salida</strong> (el guarda podrá cambiar entre modo Entrada y Salida)</span>
                </label>

                <div>
                    <x-input-label for="segundos_pantalla" value="Segundos que se muestra la ficha del estudiante en la pantalla del guarda" />
                    <x-text-input id="segundos_pantalla" name="segundos_pantalla" type="number" min="2" max="30" :value="old('segundos_pantalla', $configuracion->segundos_pantalla)" class="mt-1 block w-32" required />
                </div>

                <fieldset class="rounded-md border border-slate-200 p-4 space-y-3">
                    <legend class="px-1 text-sm font-semibold text-slate-900">Avisos al encargado legal</legend>
                    <p class="text-xs text-slate-500">Cuando el estudiante marca, se envía un mensaje al teléfono y/o correo del encargado registrado en la ficha del estudiante.</p>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        @foreach (['avisar_por_whatsapp' => 'Enviar por WhatsApp', 'avisar_por_correo' => 'Enviar por correo', 'avisar_entrada' => 'Avisar la entrada', 'avisar_salida' => 'Avisar la salida'] as $campo => $texto)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="hidden" name="{{ $campo }}" value="0">
                                <input type="checkbox" name="{{ $campo }}" value="1" class="rounded border-slate-300 text-ctp-700" @checked(old($campo, $configuracion->{$campo}))>
                                {{ $texto }}
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-500">El aviso de salida solo se envía si también está activado «Registrar la salida» y el guarda marca en modo <strong>Salida</strong>.</p>
                </fieldset>

                <div>
                    <x-input-label for="logo" value="Logo del colegio (opcional, aparece en el carnet)" />
                    <div class="mt-1 flex items-center gap-3">
                        @if ($configuracion->logo)
                            <img src="{{ route('logo') }}" alt="Logo actual" class="h-12 w-12 object-contain">
                        @endif
                        <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                               class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-ctp-50 file:px-3 file:py-2 file:text-ctp-800">
                    </div>
                </div>

                <x-primary-button>Guardar configuración</x-primary-button>
            </form>
        </x-tarjeta>

        <x-tarjeta titulo="Estado de los avisos" class="lg:col-span-2 lg:order-last">
            <div class="grid md:grid-cols-2 gap-6">
                <ul class="space-y-2 text-sm">
                    <li>
                        @if ($whatsAppConfigurado)
                            <span class="text-emerald-800 font-semibold">✓ WhatsApp configurado</span>
                            <span class="text-slate-500">(plantilla «{{ config('services.whatsapp.plantilla') }}»)</span>
                        @else
                            <span class="text-red-800 font-semibold">✕ WhatsApp sin configurar</span>
                            <span class="block text-slate-500">Faltan <code>WHATSAPP_TOKEN</code> y <code>WHATSAPP_PHONE_NUMBER_ID</code> en el archivo <code>.env</code> (cuenta de WhatsApp Business de Meta).</span>
                        @endif
                    </li>
                    <li>
                        @if ($correoConfigurado)
                            <span class="text-emerald-800 font-semibold">✓ Correo configurado</span>
                            <span class="text-slate-500">(se envía desde {{ config('mail.from.address') }})</span>
                        @else
                            <span class="text-red-800 font-semibold">✕ Correo en modo de prueba</span>
                            <span class="block text-slate-500">Los correos no salen: configure <code>MAIL_MAILER=smtp</code> y los datos del servidor en el archivo <code>.env</code>.</span>
                        @endif
                    </li>
                    <li>
                        @if ($avisosFallidosHoy > 0)
                            <span class="text-red-800 font-semibold">✕ {{ $avisosFallidosHoy }} aviso(s) fallaron hoy.</span>
                            <span class="block text-slate-500">El motivo aparece en la ficha de cada estudiante («Últimos avisos enviados»).</span>
                        @else
                            <span class="text-slate-600">Ningún aviso ha fallado hoy.</span>
                        @endif
                    </li>
                </ul>

                <form method="POST" action="{{ route('configuracion.probar-avisos') }}" class="space-y-3">
                    @csrf
                    <p class="text-sm font-semibold text-slate-900">Enviar un aviso de prueba</p>
                    @if ($resultados = session('resultado_prueba'))
                        <ul class="rounded-md bg-slate-50 border px-3 py-2 text-sm space-y-1">
                            @foreach ($resultados as $resultado)
                                <li>{{ $resultado }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <x-input-label for="telefono_prueba" value="Teléfono (WhatsApp)" />
                            <x-text-input id="telefono_prueba" name="telefono_prueba" type="tel" :value="old('telefono_prueba')" class="mt-1 block w-40" placeholder="8888-7777" />
                        </div>
                        <div>
                            <x-input-label for="correo_prueba" value="Correo" />
                            <x-text-input id="correo_prueba" name="correo_prueba" type="email" :value="old('correo_prueba')" class="mt-1 block w-56" />
                        </div>
                        <x-secondary-button type="submit">Enviar prueba</x-secondary-button>
                    </div>
                </form>
            </div>
        </x-tarjeta>

        <x-tarjeta titulo="Feriados y días sin lecciones">
            <p class="text-sm text-slate-600 mb-4">Estos días no se cuentan como ausencia en los informes (feriados, vacaciones de medio año, actividades institucionales, etc.).</p>

            <form method="POST" action="{{ route('dias-no-lectivos.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <x-input-label for="fecha" value="Desde" />
                    <x-text-input id="fecha" name="fecha" type="date" :value="old('fecha')" class="mt-1 block" required />
                </div>
                <div>
                    <x-input-label for="fecha_fin" value="Hasta (opcional)" />
                    <x-text-input id="fecha_fin" name="fecha_fin" type="date" :value="old('fecha_fin')" class="mt-1 block" />
                </div>
                <div class="grow">
                    <x-input-label for="descripcion" value="Descripción" />
                    <x-text-input id="descripcion" name="descripcion" :value="old('descripcion')" class="mt-1 block w-full" placeholder="Vacaciones de medio año" required />
                </div>
                <x-primary-button>Agregar</x-primary-button>
            </form>

            <ul class="mt-4 divide-y divide-slate-100 max-h-96 overflow-y-auto">
                @forelse ($diasNoLectivos as $dia)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <span><span class="tabular-nums font-medium">{{ $dia->fecha->format('d/m/Y') }}</span> <span class="capitalize text-slate-500">{{ $dia->fecha->translatedFormat('l') }}</span> · {{ $dia->descripcion }}</span>
                        <form method="POST" action="{{ route('dias-no-lectivos.destroy', $dia) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-700 hover:underline">Quitar</button>
                        </form>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">No hay días no lectivos registrados este año.</li>
                @endforelse
            </ul>
        </x-tarjeta>
    </div>
</x-app-layout>
