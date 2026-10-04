<?php

namespace Database\Factories;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asistencia>
 */
class AsistenciaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estudiante_id' => Estudiante::factory(),
            'fecha' => today()->toDateString(),
            'hora_entrada' => '06:50:00',
            'estado' => EstadoAsistencia::Presente,
            'usuario_id' => User::factory()->guarda(),
        ];
    }

    public function tarde(): static
    {
        return $this->state(fn (array $attributes) => [
            'hora_entrada' => '07:25:00',
            'estado' => EstadoAsistencia::Tarde,
        ]);
    }
}
