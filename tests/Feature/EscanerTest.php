<?php

namespace Tests\Feature;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EscanerTest extends TestCase
{
    use RefreshDatabase;

    private User $guarda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setTime(6, 45));
        $this->guarda = User::factory()->guarda()->create();
        Configuracion::actual()->update(['hora_entrada' => '07:00:00', 'minutos_tolerancia' => 10]);
    }

    public function test_scanning_a_card_registers_the_entry_and_returns_the_student_data(): void
    {
        $this->travelTo(now()->setTime(6, 45, 12));
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertOk()
            ->assertJson([
                'resultado' => 'entrada',
                'tipo' => 'exito',
                'estudiante' => [
                    'nombre' => $estudiante->nombreCompleto(),
                    'cedula' => $estudiante->cedula,
                    'seccion' => $estudiante->seccionNombre(),
                ],
                'hora_entrada' => '06:45:12 AM',
                'total_hoy' => 1,
            ]);

        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'fecha' => today()->toDateString(),
            'hora_entrada' => '06:45:12',
            'estado' => EstadoAsistencia::Presente->value,
            'usuario_id' => $this->guarda->id,
        ]);
    }

    public function test_an_entry_after_the_tolerance_is_registered_as_late(): void
    {
        $this->travelTo(now()->setTime(7, 10, 1));
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertJson(['resultado' => 'tarde', 'tipo' => 'advertencia']);

        $this->assertSame(EstadoAsistencia::Tarde, Asistencia::sole()->estado);
    }

    public function test_an_entry_exactly_at_the_limit_is_on_time(): void
    {
        $this->travelTo(now()->setTime(7, 10, 0));
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertJson(['resultado' => 'entrada']);
    }

    public function test_scanning_the_same_card_twice_the_same_day_is_detected_as_duplicate(): void
    {
        $this->travelTo(now()->setTime(6, 40));
        $estudiante = Estudiante::factory()->create();
        $this->actingAs($this->guarda)->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra]);

        $this->travelTo(now()->setTime(6, 42));
        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertJson([
                'resultado' => 'duplicado',
                'titulo' => 'YA TIENE REGISTRADA SU ENTRADA',
                'hora_entrada' => '06:40:00 AM',
            ]);

        $this->assertSame(1, Asistencia::count());
    }

    public function test_the_student_can_scan_again_on_another_day(): void
    {
        $estudiante = Estudiante::factory()->create();
        Asistencia::factory()->for($estudiante)->create(['fecha' => today()->subDay()]);

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertJson(['tipo' => 'exito']);

        $this->assertSame(2, $estudiante->asistencias()->count());
    }

    public function test_an_unknown_code_is_reported_without_registering_anything(): void
    {
        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => 'EST999999'])
            ->assertJson(['resultado' => 'no_encontrado', 'tipo' => 'error', 'estudiante' => null]);

        $this->assertSame(0, Asistencia::count());
    }

    public function test_an_inactive_student_can_not_register(): void
    {
        $estudiante = Estudiante::factory()->inactivo()->create();

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertJson(['resultado' => 'inactivo', 'tipo' => 'error']);

        $this->assertSame(0, Asistencia::count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function formasDeDigitarLaCedula(): array
    {
        return [
            'con guiones' => ['2-0876-0123'],
            'sin guiones' => ['208760123'],
            'con espacios alrededor' => ['  2-0876-0123 '],
        ];
    }

    #[DataProvider('formasDeDigitarLaCedula')]
    public function test_the_guard_can_type_the_id_number_when_the_student_forgot_the_card(string $digitado): void
    {
        $estudiante = Estudiante::factory()->create(['cedula' => '2-0876-0123']);

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $digitado])
            ->assertJson(['tipo' => 'exito', 'estudiante' => ['cedula' => '2-0876-0123']]);

        $this->assertTrue($estudiante->asistencias()->exists());
    }

    public function test_exit_mode_records_the_exit_time_when_enabled(): void
    {
        Configuracion::actual()->update(['registrar_salida' => true]);
        $estudiante = Estudiante::factory()->create();
        $asistencia = Asistencia::factory()->for($estudiante)->create();
        $this->travelTo(now()->setTime(16, 20, 5));

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra, 'modo' => 'salida'])
            ->assertJson(['resultado' => 'salida', 'hora_salida' => '04:20:05 PM']);

        $asistencia->refresh();
        $this->assertSame('16:20:05', $asistencia->hora_salida);
        $this->assertSame($this->guarda->id, $asistencia->usuario_salida_id);
    }

    public function test_exit_without_an_entry_is_rejected(): void
    {
        Configuracion::actual()->update(['registrar_salida' => true]);
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra, 'modo' => 'salida'])
            ->assertJson(['resultado' => 'sin_entrada', 'tipo' => 'error']);

        $this->assertSame(0, Asistencia::count());
    }

    public function test_exit_mode_is_ignored_when_exits_are_disabled(): void
    {
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra, 'modo' => 'salida'])
            ->assertJson(['resultado' => 'entrada']);
    }

    public function test_a_student_from_a_past_enrollment_year_is_alerted_and_not_registered(): void
    {
        $estudiante = Estudiante::factory()->matriculaVencida()->create();

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertOk()
            ->assertJson([
                'resultado' => 'matricula_vencida',
                'tipo' => 'advertencia',
                'titulo' => 'MATRÍCULA '.now()->year.' NO ACTUALIZADA',
                'estudiante' => ['cedula' => $estudiante->cedula],
            ]);

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_a_student_already_enrolled_for_next_year_can_register(): void
    {
        $estudiante = Estudiante::factory()->create(['anio_matricula' => now()->year + 1]);

        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertJson(['resultado' => 'entrada']);
    }

    public function test_the_code_is_required(): void
    {
        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo');
    }

    public function test_the_guard_screen_renders(): void
    {
        $this->actingAs($this->guarda)->get(route('escaner.index'))->assertOk()->assertSee('CONTROL DE INGRESO');
    }

    public function test_the_management_role_can_not_register_entries(): void
    {
        $estudiante = Estudiante::factory()->create();

        $this->actingAs(User::factory()->direccion()->create())
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra])
            ->assertForbidden();
    }

    public function test_guests_can_not_register_entries(): void
    {
        $this->postJson(route('escaner.registrar'), ['codigo' => 'EST000001'])->assertUnauthorized();
    }
}
