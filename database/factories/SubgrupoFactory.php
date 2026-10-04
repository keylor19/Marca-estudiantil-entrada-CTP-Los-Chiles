<?php

namespace Database\Factories;

use App\Models\Seccion;
use App\Models\Subgrupo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subgrupo>
 */
class SubgrupoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seccion_id' => Seccion::factory(),
            'nombre' => fake()->unique()->randomElement(range('A', 'Z')),
            'especialidad' => fake()->randomElement(['Contabilidad', 'Informática en Desarrollo de Software', 'Agroecología', 'Turismo Rural', null]),
            'activo' => true,
        ];
    }
}
