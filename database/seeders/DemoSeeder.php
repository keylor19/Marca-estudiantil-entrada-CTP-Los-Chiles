<?php

namespace Database\Seeders;

use App\Enums\EstadoAsistencia;
use App\Enums\Rol;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\User;
use App\Services\CalendarioLectivo;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba: secciones de 7.° a 12.°, estudiantes y un mes de marcas.
 * NO ejecutar en producción.
 */
class DemoSeeder extends Seeder
{
    private const NIVELES = [
        7 => 'Sétimo', 8 => 'Octavo', 9 => 'Noveno',
        10 => 'Décimo', 11 => 'Undécimo', 12 => 'Duodécimo',
    ];

    private const ESPECIALIDADES = ['Contabilidad', 'Informática en Desarrollo de Software', 'Agroecología', 'Turismo Rural'];

    public function run(CalendarioLectivo $calendario): void
    {
        $this->call(DatabaseSeeder::class);

        $guarda = User::query()->firstOrCreate(['usuario' => 'guarda'], [
            'name' => 'Guarda Entrada Principal', 'rol' => Rol::Guarda, 'activo' => true, 'password' => 'Guarda2026!',
        ]);

        User::query()->firstOrCreate(['usuario' => 'direccion'], [
            'name' => 'Dirección', 'rol' => Rol::Direccion, 'activo' => true, 'password' => 'Direccion2026!',
        ]);

        foreach (self::NIVELES as $nivel => $nombreNivel) {
            foreach ([1, 2] as $grupo) {
                $seccion = Seccion::query()->firstOrCreate(['nombre' => "{$nivel}-{$grupo}"], ['nivel' => $nombreNivel]);

                foreach (['A', 'B'] as $indice => $subgrupo) {
                    $subgrupo = $seccion->subgrupos()->firstOrCreate(['nombre' => $subgrupo], [
                        'especialidad' => $nivel >= 10 ? self::ESPECIALIDADES[($grupo - 1) * 2 + $indice] : null,
                    ]);

                    Estudiante::factory()->count(12)->for($subgrupo)->create();
                }
            }
        }

        $marcas = [];
        $ahora = now();

        foreach ($calendario->diasLectivos(today()->subDays(30), today()->subDay()) as $fecha) {
            foreach (Estudiante::query()->pluck('id') as $estudianteId) {
                $azar = random_int(1, 100);

                if ($azar > 92) {
                    continue; // ausente
                }

                $tarde = $azar > 84;
                $marcas[] = [
                    'estudiante_id' => $estudianteId,
                    'fecha' => $fecha,
                    'hora_entrada' => $tarde ? sprintf('07:%02d:%02d', random_int(11, 45), random_int(0, 59)) : sprintf('06:%02d:%02d', random_int(30, 59), random_int(0, 59)),
                    'estado' => ($tarde ? EstadoAsistencia::Tarde : EstadoAsistencia::Presente)->value,
                    'usuario_id' => $guarda->id,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
        }

        foreach (array_chunk($marcas, 500) as $lote) {
            Asistencia::query()->insert($lote);
        }
    }
}
