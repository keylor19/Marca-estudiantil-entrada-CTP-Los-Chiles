<x-app-layout :titulo="$seccion->exists ? 'Sección '.$seccion->nombre : 'Nueva sección'">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-900">{{ $seccion->exists ? 'Sección '.$seccion->nombre : 'Nueva sección' }}</h2>
        <x-boton-enlace variante="secundario" :href="route('secciones.index')">Volver</x-boton-enlace>
    </x-slot>

    <div class="grid lg:grid-cols-2 gap-6">
        <x-tarjeta titulo="Datos de la sección">
            <form method="POST" action="{{ $seccion->exists ? route('secciones.update', $seccion) : route('secciones.store') }}" class="space-y-4">
                @csrf
                @if ($seccion->exists)
                    @method('PUT')
                @endif
                <div>
                    <x-input-label for="nombre" value="Nombre de la sección *" />
                    <x-text-input id="nombre" name="nombre" :value="old('nombre', $seccion->nombre)" class="mt-1 block w-full" placeholder="10-2" required />
                </div>
                <div>
                    <x-input-label for="nivel" value="Nivel *" />
                    <x-select id="nivel" name="nivel" class="mt-1 block w-full" required>
                        @foreach (['Sétimo', 'Octavo', 'Noveno', 'Décimo', 'Undécimo', 'Duodécimo'] as $nivel)
                            <option @selected(old('nivel', $seccion->nivel) === $nivel)>{{ $nivel }}</option>
                        @endforeach
                    </x-select>
                </div>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-ctp-700" @checked(old('activo', $seccion->activo))>
                    <span class="text-sm">Sección activa</span>
                </label>
                @unless ($seccion->exists)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="crear_subgrupos" value="1" class="rounded border-slate-300 text-ctp-700" checked>
                        <span class="text-sm">Crear automáticamente los subgrupos A y B</span>
                    </label>
                @endunless
                <x-primary-button>Guardar</x-primary-button>
            </form>

            @if ($seccion->exists)
                <form method="POST" action="{{ route('secciones.destroy', $seccion) }}" class="mt-6 border-t pt-4" onsubmit="return confirm('¿Eliminar la sección?')">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm text-red-700 hover:underline">Eliminar sección (solo si no tiene estudiantes)</button>
                </form>
            @endif
        </x-tarjeta>

        @if ($seccion->exists)
            <x-tarjeta titulo="Subgrupos">
                <div class="space-y-3">
                    @foreach ($seccion->subgrupos as $subgrupo)
                        <div class="flex flex-wrap items-end gap-2 border-b pb-3">
                            <form method="POST" action="{{ route('subgrupos.update', $subgrupo) }}" class="flex flex-wrap items-end gap-2 grow">
                                @csrf
                                @method('PUT')
                                <div class="w-20">
                                    <x-input-label value="Subgrupo" />
                                    <x-text-input name="nombre" :value="$subgrupo->nombre" class="mt-1 block w-full" required />
                                </div>
                                <div class="grow">
                                    <x-input-label value="Especialidad" />
                                    <x-text-input name="especialidad" :value="$subgrupo->especialidad" class="mt-1 block w-full" />
                                </div>
                                <x-secondary-button type="submit">Guardar</x-secondary-button>
                            </form>
                            <span class="text-sm text-slate-500 pb-2">{{ $subgrupo->estudiantes_count }} est.</span>
                            @if ($subgrupo->estudiantes_count === 0)
                                <form method="POST" action="{{ route('subgrupos.destroy', $subgrupo) }}" onsubmit="return confirm('¿Eliminar el subgrupo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-sm text-red-700 hover:underline pb-2">Eliminar</button>
                                </form>
                            @endif
                        </div>
                    @endforeach

                    <form method="POST" action="{{ route('subgrupos.store', $seccion) }}" class="flex flex-wrap items-end gap-2 pt-2">
                        @csrf
                        <div class="w-20">
                            <x-input-label value="Nuevo" />
                            <x-text-input name="nombre" class="mt-1 block w-full" placeholder="C" required />
                        </div>
                        <div class="grow">
                            <x-input-label value="Especialidad" />
                            <x-text-input name="especialidad" class="mt-1 block w-full" />
                        </div>
                        <x-primary-button>Agregar</x-primary-button>
                    </form>
                </div>
            </x-tarjeta>
        @endif
    </div>
</x-app-layout>
