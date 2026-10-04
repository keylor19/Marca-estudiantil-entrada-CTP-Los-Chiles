<?php

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Jobs\EnviarAvisoEncargado;
use App\Models\Asistencia;
use App\Models\Configuracion;

/**
 * Decide si se avisa al encargado de una entrada o salida, y por cuáles canales.
 * El envío se hace en segundo plano (cola) para no atrasar la pantalla del guarda.
 */
class AvisoEncargado
{
    public const ENTRADA = 'entrada';

    public const SALIDA = 'salida';

    public const WHATSAPP = 'whatsapp';

    public const CORREO = 'correo';

    public function programar(Asistencia $asistencia, string $tipo): void
    {
        $configuracion = Configuracion::actual();
        $estudiante = $asistencia->estudiante;

        $avisarEsteTipo = $tipo === self::ENTRADA ? $configuracion->avisar_entrada : $configuracion->avisar_salida;

        if (! $avisarEsteTipo || ! $estudiante->avisar_encargado) {
            return;
        }

        if ($configuracion->avisar_por_whatsapp && $estudiante->telefonoWhatsApp()) {
            EnviarAvisoEncargado::dispatch($asistencia->id, $tipo, self::WHATSAPP);
        }

        if ($configuracion->avisar_por_correo && filled($estudiante->encargado_correo)) {
            EnviarAvisoEncargado::dispatch($asistencia->id, $tipo, self::CORREO);
        }
    }

    /**
     * Datos del mensaje (iguales para WhatsApp y correo).
     *
     * @return array{colegio: string, encargado: string, estudiante: string, seccion: string, accion: string, fecha: string, hora: string}
     */
    public static function datos(Asistencia $asistencia, string $tipo): array
    {
        $estudiante = $asistencia->estudiante;

        $accion = match (true) {
            $tipo === self::SALIDA => 'salida',
            $asistencia->estado === EstadoAsistencia::Tarde => 'entrada (llegada tardía)',
            default => 'entrada',
        };

        return [
            'colegio' => Configuracion::actual()->nombre_colegio,
            'encargado' => $estudiante->encargado_nombre ?: 'encargado(a)',
            'estudiante' => $estudiante->nombreCompleto(),
            'seccion' => $estudiante->seccionNombre(),
            'accion' => $accion,
            'fecha' => $asistencia->fecha->format('d/m/Y'),
            'hora' => Asistencia::formatoHora($tipo === self::SALIDA ? $asistencia->hora_salida : $asistencia->hora_entrada),
        ];
    }

    /**
     * Variables de la plantilla de WhatsApp: {{1}} estudiante, {{2}} acción, {{3}} fecha, {{4}} hora.
     *
     * @param  array{estudiante: string, accion: string, fecha: string, hora: string}  $datos
     * @return list<string>
     */
    public static function variablesWhatsApp(array $datos): array
    {
        return [$datos['estudiante'], $datos['accion'], $datos['fecha'], $datos['hora']];
    }
}
