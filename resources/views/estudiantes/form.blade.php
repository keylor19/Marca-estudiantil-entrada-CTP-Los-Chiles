<x-app-layout :titulo="$estudiante->exists ? 'Editar estudiante' : 'Nuevo estudiante'">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">{{ $estudiante->exists ? 'Editar estudiante' : 'Nuevo estudiante' }}</h2>
        <x-boton-enlace variante="secundario" :href="$estudiante->exists ? route('estudiantes.show', $estudiante) : route('estudiantes.index')">Volver</x-boton-enlace>
    </x-slot>

    @if ($secciones->isEmpty())
        <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
            Primero debe <a href="{{ route('secciones.create') }}" class="underline font-semibold">crear una sección</a> para poder asignar estudiantes.
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $estudiante->exists ? route('estudiantes.update', $estudiante) : route('estudiantes.store') }}"
          class="grid lg:grid-cols-[1fr_18rem] gap-6">
        @csrf
        @if ($estudiante->exists)
            @method('PUT')
        @endif

        <x-tarjeta titulo="Datos personales">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="cedula" value="Cédula *" />
                    <x-text-input id="cedula" name="cedula" :value="old('cedula', $estudiante->cedula)" class="mt-1 block w-full" required placeholder="2-0876-0123" />
                    <x-input-error :messages="$errors->get('cedula')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="fecha_nacimiento" value="Fecha de nacimiento" />
                    <x-text-input id="fecha_nacimiento" name="fecha_nacimiento" type="date" :value="old('fecha_nacimiento', $estudiante->fecha_nacimiento?->toDateString())" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('fecha_nacimiento')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="nombre" value="Nombre *" />
                    <x-text-input id="nombre" name="nombre" :value="old('nombre', $estudiante->nombre)" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="apellido1" value="Primer apellido *" />
                    <x-text-input id="apellido1" name="apellido1" :value="old('apellido1', $estudiante->apellido1)" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('apellido1')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="apellido2" value="Segundo apellido" />
                    <x-text-input id="apellido2" name="apellido2" :value="old('apellido2', $estudiante->apellido2)" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('apellido2')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="subgrupo_id" value="Sección / subgrupo *" />
                    <x-select id="subgrupo_id" name="subgrupo_id" class="mt-1 block w-full" required>
                        <option value="">Seleccione…</option>
                        @foreach ($secciones as $seccion)
                            <optgroup label="Sección {{ $seccion->nombre }} ({{ $seccion->nivel }})">
                                @foreach ($seccion->subgrupos as $subgrupo)
                                    <option value="{{ $subgrupo->id }}" @selected(old('subgrupo_id', $estudiante->subgrupo_id) == $subgrupo->id)>
                                        {{ trim($seccion->nombre.' '.$subgrupo->nombre) }}{{ $subgrupo->especialidad ? ' · '.$subgrupo->especialidad : '' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('subgrupo_id')" class="mt-1" />
                </div>
                @php($cursoLectivo = \App\Models\Estudiante::cursoLectivo())
                @php($anioMatricula = (int) old('anio_matricula', $estudiante->anio_matricula))
                <div class="sm:col-span-2">
                    <x-input-label for="anio_matricula" value="Año de matrícula *" />
                    <x-select id="anio_matricula" name="anio_matricula" class="mt-1 block" required>
                        @if (! $anioMatricula)
                            <option value="">Sin matrícula registrada</option>
                        @endif
                        @foreach (collect(range($cursoLectivo + 1, $cursoLectivo - 2))->push($anioMatricula)->filter()->unique()->sortDesc() as $anio)
                            <option value="{{ $anio }}" @selected($anioMatricula === $anio)>{{ $anio }}{{ $anio === $cursoLectivo ? ' (año en curso)' : '' }}</option>
                        @endforeach
                    </x-select>
                    @if ($estudiante->exists && ! $estudiante->matriculaVigente())
                        <p class="mt-1 text-sm text-amber-800">⚠ La matrícula de este estudiante está vencida: no puede marcar la entrada hasta que se actualice a {{ $cursoLectivo }}.</p>
                    @endif
                    <x-input-error :messages="$errors->get('anio_matricula')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-ctp-700" @checked(old('activo', $estudiante->activo))>
                        <span class="text-sm text-slate-700">Estudiante activo (puede marcar la entrada)</span>
                    </label>
                </div>
            </div>

            <h3 class="mt-8 mb-3 font-semibold text-slate-900 border-t pt-5">Encargado legal</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="encargado_nombre" value="Nombre completo del encargado" />
                    <x-text-input id="encargado_nombre" name="encargado_nombre" :value="old('encargado_nombre', $estudiante->encargado_nombre)" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('encargado_nombre')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="encargado_parentesco" value="Parentesco" />
                    <x-text-input id="encargado_parentesco" name="encargado_parentesco" list="parentescos" :value="old('encargado_parentesco', $estudiante->encargado_parentesco)" class="mt-1 block w-full" placeholder="Madre, Padre, Abuela…" />
                    <datalist id="parentescos">
                        @foreach (['Madre', 'Padre', 'Abuela', 'Abuelo', 'Tía', 'Tío', 'Hermana', 'Hermano', 'Tutor(a) legal'] as $parentesco)
                            <option value="{{ $parentesco }}"></option>
                        @endforeach
                    </datalist>
                    <x-input-error :messages="$errors->get('encargado_parentesco')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="encargado_telefono" value="Teléfono / WhatsApp" />
                    <x-text-input id="encargado_telefono" name="encargado_telefono" type="tel" :value="old('encargado_telefono', $estudiante->encargado_telefono)" class="mt-1 block w-full" placeholder="8888-7777" />
                    <p class="mt-1 text-xs text-slate-500">Si tiene 8 dígitos se toma como número de Costa Rica (+506).</p>
                    <x-input-error :messages="$errors->get('encargado_telefono')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="encargado_correo" value="Correo electrónico" />
                    <x-text-input id="encargado_correo" name="encargado_correo" type="email" :value="old('encargado_correo', $estudiante->encargado_correo)" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('encargado_correo')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="avisar_encargado" value="0">
                        <input type="checkbox" name="avisar_encargado" value="1" class="rounded border-slate-300 text-ctp-700" @checked(old('avisar_encargado', $estudiante->avisar_encargado ?? true))>
                        <span class="text-sm text-slate-700">Avisar al encargado cuando el estudiante entra o sale del colegio (WhatsApp / correo)</span>
                    </label>
                </div>
            </div>

            @if ($estudiante->exists)
                <p class="mt-4 text-sm text-slate-500">Código de barras: <span class="font-mono font-semibold text-slate-800">{{ $estudiante->codigo_barra }}</span> (no cambia aunque se editen los datos).</p>
            @else
                <p class="mt-4 text-sm text-slate-500">El código de barras único se genera automáticamente al guardar.</p>
            @endif
        </x-tarjeta>

        <x-tarjeta titulo="Fotografía">
            <div x-data="{ vista: '{{ $estudiante->exists ? $estudiante->fotoUrl() : asset('img/sin-foto.svg') }}' }" class="space-y-3">
                <img :src="vista" alt="Foto del estudiante" class="w-full aspect-[3/4] object-cover rounded-md bg-slate-200">
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp"
                       @change="vista = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : vista"
                       class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-ctp-50 file:px-3 file:py-2 file:text-ctp-800">
                <p class="text-xs text-slate-500">JPG, PNG o WEBP, máximo 5 MB. Preferiblemente vertical, tipo foto de carnet.</p>
                <x-input-error :messages="$errors->get('foto')" />
            </div>
        </x-tarjeta>

        <div class="lg:col-span-2 flex justify-end gap-2">
            <x-primary-button>Guardar</x-primary-button>
        </div>
    </form>
</x-app-layout>
