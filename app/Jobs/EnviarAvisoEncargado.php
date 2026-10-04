<?php

namespace App\Jobs;

use App\Mail\AvisoAsistenciaMail;
use App\Models\Asistencia;
use App\Models\Aviso;
use App\Services\AvisoEncargado;
use App\Services\ClienteWhatsApp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envía un aviso (WhatsApp o correo) al encargado y deja constancia en la tabla `avisos`.
 */
class EnviarAvisoEncargado implements ShouldQueue
{
    use Queueable;

    /**
     * Reintentos ante fallas temporales (internet caído, servidor de correo ocupado, etc.).
     */
    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120];

    public function __construct(
        public int $asistenciaId,
        public string $tipo,
        public string $canal,
    ) {}

    public function handle(ClienteWhatsApp $whatsApp): void
    {
        $asistencia = Asistencia::query()->with('estudiante.subgrupo.seccion')->find($this->asistenciaId);

        if (! $asistencia) {
            return; // la marca se eliminó antes de enviar el aviso
        }

        $datos = AvisoEncargado::datos($asistencia, $this->tipo);

        if ($this->canal === AvisoEncargado::WHATSAPP) {
            $whatsApp->enviarPlantilla($this->destino($asistencia), AvisoEncargado::variablesWhatsApp($datos));
        } else {
            Mail::to($this->destino($asistencia))->send(new AvisoAsistenciaMail($datos));
        }

        $this->registrar($asistencia, Aviso::ENVIADO);
    }

    /**
     * Se ejecuta cuando se agotaron los reintentos.
     */
    public function failed(Throwable $error): void
    {
        $asistencia = Asistencia::query()->with('estudiante')->find($this->asistenciaId);

        if ($asistencia) {
            $this->registrar($asistencia, Aviso::FALLIDO, $error->getMessage());
        }
    }

    private function registrar(Asistencia $asistencia, string $estado, ?string $error = null): void
    {
        Aviso::create([
            'estudiante_id' => $asistencia->estudiante_id,
            'asistencia_id' => $asistencia->id,
            'tipo' => $this->tipo,
            'canal' => $this->canal,
            'destino' => $this->destino($asistencia),
            'estado' => $estado,
            'error' => $error ? mb_substr($error, 0, 1000) : null,
        ]);
    }

    private function destino(Asistencia $asistencia): string
    {
        return $this->canal === AvisoEncargado::WHATSAPP
            ? (string) $asistencia->estudiante->telefonoWhatsApp()
            : (string) $asistencia->estudiante->encargado_correo;
    }
}
