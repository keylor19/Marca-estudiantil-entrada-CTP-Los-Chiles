<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
        <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">
    <title>Carnets estudiantiles · {{ $configuracion->nombre_colegio }}</title>
    <style>
        /* Carnet tamaño tarjeta CR80 (54 × 85,6 mm), de 1 a 9 por hoja carta. */
        @page { size: letter; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #0f172a; background: #e2e8f0; }
        .barra { position: sticky; top: 0; display: flex; gap: 12px; align-items: center; justify-content: space-between; padding: 10px 16px; background: #1b4028; color: #fff; font-size: 14px; }
        .barra button, .barra a { background: #f5ec8a; color: #1b4028; border: 0; border-radius: 6px; padding: 8px 14px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 14px; }
        .barra a { background: transparent; color: #fff; border: 1px solid #5bb8e3; }
        .barra .acciones { display: flex; gap: 12px; align-items: center; }
        .barra select { border-radius: 6px; border: 0; padding: 7px 8px; font-size: 14px; color: #1b4028; }
        .hoja { display: grid; grid-template-columns: repeat(var(--columnas), 54mm); gap: 3mm; justify-content: center; align-content: start; padding: 8mm 0; }
        .hoja + .hoja { border-top: 1px dashed #94a3b8; }
        .carnet { width: 54mm; height: 85.6mm; background: #fff; border: 0.2mm dashed #94a3b8; border-radius: 3mm; overflow: hidden; display: flex; flex-direction: column; break-inside: avoid; page-break-inside: avoid; }
        .encabezado { background: #1b4028; border-bottom: 1mm solid #5bb8e3; color: #fff; display: flex; align-items: center; gap: 2mm; padding: 2mm 2.5mm; }
        .encabezado img, .encabezado svg { width: 10mm; height: 10mm; object-fit: contain; flex-shrink: 0; }
        .encabezado .colegio { font-size: 2.9mm; font-weight: bold; line-height: 1.15; text-transform: uppercase; }
        .encabezado .tipo { font-size: 2.2mm; color: #f5ec8a; letter-spacing: .2mm; }
        .cuerpo { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 2mm 3mm 0; text-align: center; }
        .foto { width: 22mm; height: 28mm; object-fit: cover; border-radius: 1.5mm; border: 0.4mm solid #2f7a43; background: #e2e8f0; }
        .nombre { margin-top: 1.5mm; font-size: 3.2mm; font-weight: bold; line-height: 1.15; text-transform: uppercase; }
        .dato { font-size: 2.6mm; margin-top: .6mm; }
        .dato b { color: #214e2f; }
        .codigo { padding: 1mm 4mm 1.5mm; text-align: center; }
        .codigo svg { display: block; width: 44mm; height: 10mm; margin: 0 auto; }
        .codigo span { display: block; font-family: 'Courier New', monospace; font-size: 2.6mm; letter-spacing: .6mm; margin-top: .5mm; }
        .pie { height: 2mm; background: linear-gradient(90deg, #55ad6a 0 50%, #5bb8e3 50% 100%); }
        .vacio { text-align: center; padding: 60px; font-size: 18px; }
        @media print {
            body { background: #fff; }
            .barra { display: none; }
            .hoja { padding: 0; break-after: page; page-break-after: always; }
            .hoja:last-child { break-after: auto; page-break-after: auto; }
            .hoja + .hoja { border-top: 0; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <span>{{ $estudiantes->count() }} carnet(s) · {{ $hojas->count() }} hoja(s) carta. En la ventana de impresión elija <strong>escala 100 %</strong> y active <strong>gráficos de fondo</strong>.</span>
        <span class="acciones">
            <label>
                Carnets por hoja
                <select onchange="const url = new URL(location.href); url.searchParams.set('por_hoja', this.value); location.href = url;">
                    @foreach ($distribuciones as $cantidad)
                        <option value="{{ $cantidad }}" @selected($cantidad === $porHoja)>{{ $cantidad }}</option>
                    @endforeach
                </select>
            </label>
            <a href="{{ route('carnets.index') }}">Volver</a>
            <button onclick="window.print()">Imprimir</button>
        </span>
    </div>

    @if ($estudiantes->isEmpty())
        <p class="vacio">No hay estudiantes para imprimir con los filtros seleccionados.</p>
    @endif

    @foreach ($hojas as $hoja)
    <div class="hoja" style="--columnas: {{ $columnas }}">
        @foreach ($hoja as $estudiante)
            <article class="carnet">
                <header class="encabezado">
                    @if ($configuracion->logo)
                        <img src="{{ route('logo') }}" alt="">
                    @else
                        <x-application-logo />
                    @endif
                    <div>
                        <div class="colegio">{{ $configuracion->nombre_colegio }}</div>
                        <div class="tipo">CARNÉ ESTUDIANTIL {{ max($curso, (int) $estudiante->anio_matricula) }}</div>
                    </div>
                </header>
                <div class="cuerpo">
                    <img class="foto" src="{{ $estudiante->fotoUrl() }}" alt="">
                    <div class="nombre">{{ $estudiante->nombreCompleto() }}</div>
                    <div class="dato"><b>Cédula:</b> {{ $estudiante->cedula }}</div>
                    <div class="dato"><b>Sección:</b> {{ $estudiante->seccionNombre() }}</div>
                    @if ($estudiante->subgrupo->especialidad)
                        <div class="dato">{{ $estudiante->subgrupo->especialidad }}</div>
                    @endif
                </div>
                <div class="codigo">
                    {!! $codigos[$estudiante->id] !!}
                    <span>{{ $estudiante->codigo_barra }}</span>
                </div>
                <div class="pie"></div>
            </article>
        @endforeach
    </div>
    @endforeach
</body>
</html>
