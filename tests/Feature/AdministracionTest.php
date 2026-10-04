<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\DiaNoLectivo;
use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministracionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => Rol::Admin]);
    }

    public function test_every_admin_page_renders(): void
    {
        $asistencia = Asistencia::factory()->create();
        $estudiante = $asistencia->estudiante;

        $paginas = [
            route('dashboard'), route('escaner.index'),
            route('estudiantes.index'), route('estudiantes.create'), route('estudiantes.show', $estudiante), route('estudiantes.edit', $estudiante),
            route('estudiantes.importar'), route('estudiantes.fotos'),
            route('carnets.index'), route('secciones.index'), route('secciones.create'), route('secciones.edit', $estudiante->subgrupo->seccion_id),
            route('registros.index'), route('reportes.index'), route('reportes.grupo'), route('reportes.secciones'), route('reportes.diario'), route('reportes.estudiante'),
            route('usuarios.index'), route('usuarios.create'), route('usuarios.edit', $this->admin), route('configuracion.edit'), route('profile.edit'),
        ];

        foreach ($paginas as $pagina) {
            $this->actingAs($this->admin)->get($pagina)->assertOk();
        }
    }

    public function test_a_new_section_gets_subgroups_a_and_b(): void
    {
        $this->actingAs($this->admin)->post(route('secciones.store'), [
            'nombre' => '10 - 3',
            'nivel' => 'Décimo',
            'activo' => '1',
            'crear_subgrupos' => '1',
        ])->assertSessionHasNoErrors();

        $seccion = Seccion::sole();
        $this->assertSame('10-3', $seccion->nombre);
        $this->assertSame(['A', 'B'], $seccion->subgrupos->pluck('nombre')->all());
    }

    public function test_a_section_with_students_can_not_be_deleted(): void
    {
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('secciones.destroy', $estudiante->subgrupo->seccion_id))
            ->assertSessionHasErrors('seccion');

        $this->assertModelExists($estudiante->subgrupo->seccion);
    }

    public function test_the_admin_creates_a_guard_that_can_then_log_in(): void
    {
        $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Juan Pérez',
            'usuario' => 'Guarda01',
            'rol' => Rol::Guarda->value,
            'activo' => '1',
            'password' => 'clave-segura',
            'password_confirmation' => 'clave-segura',
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $this->post('/login', ['usuario' => 'guarda01', 'password' => 'clave-segura']);
        $this->assertAuthenticatedAs(User::query()->where('usuario', 'guarda01')->sole());
    }

    public function test_an_admin_can_not_remove_their_own_admin_role(): void
    {
        $this->actingAs($this->admin)->put(route('usuarios.update', $this->admin), [
            'name' => $this->admin->name,
            'usuario' => $this->admin->usuario,
            'rol' => Rol::Guarda->value,
            'activo' => '0',
        ])->assertSessionHasNoErrors();

        $this->admin->refresh();
        $this->assertSame(Rol::Admin, $this->admin->rol);
        $this->assertTrue($this->admin->activo);
    }

    public function test_a_user_with_marks_is_deactivated_instead_of_deleted(): void
    {
        $guarda = User::factory()->guarda()->create();
        Asistencia::factory()->create(['usuario_id' => $guarda->id]);

        $this->actingAs($this->admin)->delete(route('usuarios.destroy', $guarda));

        $this->assertFalse($guarda->refresh()->activo);
    }

    public function test_only_admins_manage_users_and_configuration(): void
    {
        $direccion = User::factory()->direccion()->create();

        $this->actingAs($direccion)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($direccion)->get(route('configuracion.edit'))->assertForbidden();
    }

    public function test_the_configuration_changes_the_late_limit_and_school_days(): void
    {
        $this->actingAs($this->admin)->put(route('configuracion.update'), [
            'nombre_colegio' => 'CTP Los Chiles',
            'hora_entrada' => '07:20',
            'minutos_tolerancia' => 5,
            'dias_lectivos' => ['1', '2', '3', '4', '5', '6'],
            'segundos_pantalla' => 5,
            'registrar_salida' => '1',
        ])->assertSessionHasNoErrors();

        $configuracion = Configuracion::actual();
        $this->assertSame('07:25:00', $configuracion->horaLimite());
        $this->assertSame([1, 2, 3, 4, 5, 6], $configuracion->dias_lectivos);
        $this->assertTrue($configuracion->registrar_salida);
    }

    public function test_a_vacation_range_registers_each_day_as_non_school_day(): void
    {
        $this->actingAs($this->admin)->post(route('dias-no-lectivos.store'), [
            'fecha' => '2026-07-06',
            'fecha_fin' => '2026-07-17',
            'descripcion' => 'Vacaciones de medio año',
        ])->assertSessionHasNoErrors();

        $this->assertSame(12, DiaNoLectivo::count());
    }

    public function test_an_admin_can_delete_a_mistaken_mark_but_management_can_not(): void
    {
        $asistencia = Asistencia::factory()->create();

        $this->actingAs(User::factory()->direccion()->create())->delete(route('registros.destroy', $asistencia))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('registros.destroy', $asistencia));

        $this->assertModelMissing($asistencia);
    }
}
