<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorSVG;

class GeneradorCodigoBarras
{
    /**
     * Código de barras Code 128 en SVG (lo leen todos los lectores USB comunes), listo para
     * insertarse en el HTML y escalarse con CSS al tamaño del carnet.
     */
    public function svg(string $codigo, float $anchoBarra = 2, float $alto = 60): string
    {
        $svg = (new BarcodeGeneratorSVG)->getBarcode($codigo, BarcodeGeneratorSVG::TYPE_CODE_128, $anchoBarra, $alto);
        $svg = substr($svg, strpos($svg, '<svg'));

        return str_replace('<svg ', '<svg preserveAspectRatio="none" role="img" aria-label="Código de barras '.e($codigo).'" ', $svg);
    }
}
