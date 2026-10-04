<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportadorExcel
{
    /**
     * Genera un archivo .xlsx y lo envía como descarga.
     *
     * @param  list<string>  $titulo  líneas de título que van antes de la tabla
     * @param  list<string>  $encabezados
     * @param  iterable<list<string|int|float|null>>  $filas
     */
    public function descargar(string $nombreArchivo, array $titulo, array $encabezados, iterable $filas): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'reporte');
        $negrita = (new Style)->withFontBold(true);

        $escritor = new Writer;
        $escritor->openToFile($ruta);

        foreach ($titulo as $linea) {
            $escritor->addRow(Row::fromValuesWithStyle([$linea], $negrita));
        }

        if ($titulo !== []) {
            $escritor->addRow(Row::fromValues([]));
        }

        $escritor->addRow(Row::fromValuesWithStyle($encabezados, $negrita));

        foreach ($filas as $fila) {
            $escritor->addRow(Row::fromValues($fila));
        }

        $escritor->close();

        return response()->download($ruta, $nombreArchivo.'.xlsx')->deleteFileAfterSend();
    }
}
