<?php

namespace App\Enums;

enum Rol: string
{
    case Admin = 'admin';
    case Direccion = 'direccion';
    case Guarda = 'guarda';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Direccion => 'Dirección / Administración',
            self::Guarda => 'Guarda',
        };
    }
}
