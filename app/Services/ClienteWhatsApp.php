<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envío de mensajes por la API oficial de WhatsApp Business (Meta Cloud API).
 */
class ClienteWhatsApp
{
    public function configurado(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    /**
     * Envía la plantilla aprobada con las variables en orden ({{1}}, {{2}}, ...).
     *
     * @param  list<string>  $variables
     *
     * @throws RuntimeException si falta configuración o Meta rechaza el mensaje
     */
    public function enviarPlantilla(string $telefono, array $variables): void
    {
        if (! $this->configurado()) {
            throw new RuntimeException('WhatsApp no está configurado: faltan WHATSAPP_TOKEN y WHATSAPP_PHONE_NUMBER_ID en el archivo .env.');
        }

        $url = sprintf('https://graph.facebook.com/%s/%s/messages', config('services.whatsapp.version'), config('services.whatsapp.phone_number_id'));

        $respuesta = Http::withToken(config('services.whatsapp.token'))
            ->timeout(15)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $telefono,
                'type' => 'template',
                'template' => [
                    'name' => config('services.whatsapp.plantilla'),
                    'language' => ['code' => config('services.whatsapp.idioma')],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => array_map(fn (string $valor) => ['type' => 'text', 'text' => $valor], $variables),
                    ]],
                ],
            ]);

        if ($respuesta->failed()) {
            throw new RuntimeException('WhatsApp rechazó el mensaje: '.($respuesta->json('error.message') ?? $respuesta->body()));
        }
    }
}
