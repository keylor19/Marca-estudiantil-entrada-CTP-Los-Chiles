<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvisoAsistenciaMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{colegio: string, encargado: string, estudiante: string, seccion: string, accion: string, fecha: string, hora: string}  $datos
     */
    public function __construct(public array $datos) {}

    public function envelope(): Envelope
    {
        $accion = str_starts_with($this->datos['accion'], 'salida') ? 'salida del' : 'entrada al';

        return new Envelope(
            subject: "{$this->datos['colegio']}: {$this->datos['estudiante']} registró su {$accion} colegio",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.aviso-asistencia');
    }
}
