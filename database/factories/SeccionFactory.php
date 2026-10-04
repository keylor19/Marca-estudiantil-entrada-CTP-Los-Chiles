<?php

namespace Database\Factories;

use App\Models\Seccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seccion>
 */
class SeccionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nivel = fake()->numberBetween(7, 12);

        return [
            'nombre' => $nivel.'-'.fake()->unique()->numberBetween(1, 99),
            'nivel' => (string) $nivel,
            'activo' => true,
        ];
    }
}
