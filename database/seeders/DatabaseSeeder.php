<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos mínimos para arrancar: configuración y un usuario administrador.
     * Para datos de prueba (secciones, estudiantes y marcas) ejecute: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        Configuracion::actual();

        User::query()->firstOrCreate(['usuario' => 'admin'], [
            'name' => 'Administrador',
            'rol' => Rol::Admin,
            'activo' => true,
            'password' => 'Admin2026!',
        ]);
    }
}
