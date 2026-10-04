<?php

namespace App\Enums;

enum EstadoAsistencia: string
{
    case Presente = 'presente';
    case Tarde = 'tarde';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::Tarde => 'Tardía',
        };
    }
}
