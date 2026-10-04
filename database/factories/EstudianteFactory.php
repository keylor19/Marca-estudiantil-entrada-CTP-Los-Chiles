<?php

namespace Database\Factories;

use App\Models\Estudiante;
use App\Models\Subgrupo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estudiante>
 */
class EstudianteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cedula' => fake()->unique()->numerify('#-0###-0###'),
            'nombre' => fake()->firstName(),
            'apellido1' => fake()->lastName(),
            'apellido2' => fake()->lastName(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-18 years', '-12 years')->format('Y-m-d'),
            'subgrupo_id' => Subgrupo::factory(),
            'activo' => true,
            'anio_matricula' => now()->year,
            'encargado_nombre' => fake()->name(),
            'encargado_parentesco' => fake()->randomElement(['Madre', 'Padre', 'Abuela', 'Tía']),
            'encargado_telefono' => fake()->numerify('8###-####'),
            'encargado_correo' => fake()->safeEmail(),
            'avisar_encargado' => true,
        ];
    }

    public function sinEncargado(): static
    {
        return $this->state(fn (array $attributes) => [
            'encargado_nombre' => null,
            'encargado_parentesco' => null,
            'encargado_telefono' => null,
            'encargado_correo' => null,
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => ['activo' => false]);
    }

    public function matriculaVencida(): static
    {
        return $this->state(fn (array $attributes) => ['anio_matricula' => now()->year - 1]);
    }
}
