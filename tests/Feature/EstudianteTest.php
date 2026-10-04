<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Subgrupo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EstudianteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        Storage::fake('local');
    }

    public function test_creating_a_student_generates_a_unique_barcode_from_the_id(): void
    {
        $subgrupo = Subgrupo::factory()->create();

        $this->actingAs($this->admin)->post(route('estudiantes.store'), [
            'cedula' => '2-0876-0123',
            'nombre' => 'María José',
            'apellido1' => 'Rodríguez',
            'apellido2' => 'Pérez',
            'subgrupo_id' => $subgrupo->id,
            'activo' => '1',
        ])->assertSessionHasNoErrors();

        $estudiante = Estudiante::sole();
        $this->assertSame('EST'.str_pad((string) $estudiante->id, 6, '0', STR_PAD_LEFT), $estudiante->codigo_barra);

        $segundo = Estudiante::factory()->create();
        $this->assertNotSame($estudiante->codigo_barra, $segundo->codigo_barra);
    }

    public function test_the_barcode_does_not_change_when_the_student_is_edited(): void
    {
        $estudiante = Estudiante::factory()->create();
        $codigo = $estudiante->codigo_barra;

        $this->actingAs($this->admin)->put(route('estudiantes.update', $estudiante), [
            'cedula' => $estudiante->cedula,
            'nombre' => 'Otro nombre',
            'apellido1' => $estudiante->apellido1,
            'subgrupo_id' => $estudiante->subgrupo_id,
            'activo' => '1',
            'codigo_barra' => 'HACK',
        ])->assertSessionHasNoErrors();

        $this->assertSame($codigo, $estudiante->refresh()->codigo_barra);
        $this->assertSame('Otro nombre', $estudiante->nombre);
    }

    public function test_the_id_number_must_be_unique(): void
    {
        $existente = Estudiante::factory()->create();

        $this->actingAs($this->admin)->post(route('estudiantes.store'), [
            'cedula' => $existente->cedula,
            'nombre' => 'Ana',
            'apellido1' => 'Soto',
            'subgrupo_id' => $existente->subgrupo_id,
        ])->assertSessionHasErrors('cedula');
    }

    public function test_the_photo_is_resized_stored_privately_and_served_only_to_logged_in_users(): void
    {
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($this->admin)->put(route('estudiantes.update', $estudiante), [
            'cedula' => $estudiante->cedula,
            'nombre' => $estudiante->nombre,
            'apellido1' => $estudiante->apellido1,
            'subgrupo_id' => $estudiante->subgrupo_id,
            'activo' => '1',
            'foto' => UploadedFile::fake()->image('foto.png', 1200, 1600),
        ])->assertSessionHasNoErrors();

        $ruta = $estudiante->refresh()->foto;
        Storage::disk('local')->assertExists($ruta);
        $this->assertSame([600, 800], array_slice(getimagesizefromstring(Storage::disk('local')->get($ruta)), 0, 2));

        $this->actingAs(User::factory()->guarda()->create())->get(route('estudiantes.foto', $estudiante))->assertOk();
        auth()->logout();
        $this->get(route('estudiantes.foto', $estudiante))->assertRedirect(route('login'));
    }

    public function test_deleting_a_student_with_attendance_only_deactivates_it(): void
    {
        $estudiante = Estudiante::factory()->create();
        Asistencia::factory()->for($estudiante)->create();

        $this->actingAs($this->admin)->delete(route('estudiantes.destroy', $estudiante));

        $this->assertFalse($estudiante->refresh()->activo);
    }

    public function test_deleting_a_student_without_attendance_removes_it(): void
    {
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($this->admin)->delete(route('estudiantes.destroy', $estudiante));

        $this->assertModelMissing($estudiante);
    }

    public function test_the_list_can_be_searched_by_name_and_id_number(): void
    {
        $buscado = Estudiante::factory()->create(['nombre' => 'Carlos', 'apellido1' => 'Rodríguez', 'cedula' => '1-2345-6789']);
        Estudiante::factory()->create(['nombre' => 'Ana', 'apellido1' => 'Soto']);

        foreach (['carlos rodríguez', '2345'] as $texto) {
            $this->actingAs($this->admin)
                ->get(route('estudiantes.index', ['q' => $texto]))
                ->assertOk()
                ->assertViewHas('estudiantes', fn ($pagina) => $pagina->pluck('id')->all() === [$buscado->id]);
        }
    }

    public function test_students_without_the_current_enrollment_are_listed_and_can_be_disabled_at_once(): void
    {
        $vigente = Estudiante::factory()->create();
        $vencido = Estudiante::factory()->matriculaVencida()->create();

        $this->actingAs($this->admin)
            ->get(route('estudiantes.index', ['estado' => 'vencidos']))
            ->assertOk()
            ->assertSee('no están en la matrícula '.now()->year)
            ->assertSee($vencido->cedula)
            ->assertDontSee($vigente->cedula);

        $this->actingAs($this->admin)
            ->post(route('estudiantes.deshabilitar-sin-matricula'))
            ->assertRedirect();

        $this->assertFalse($vencido->refresh()->activo);
        $this->assertTrue($vigente->refresh()->activo);
    }

    public function test_editing_a_student_renews_the_enrollment_year(): void
    {
        $estudiante = Estudiante::factory()->matriculaVencida()->create();

        $this->actingAs($this->admin)->put(route('estudiantes.update', $estudiante), [
            'cedula' => $estudiante->cedula,
            'nombre' => $estudiante->nombre,
            'apellido1' => $estudiante->apellido1,
            'subgrupo_id' => $estudiante->subgrupo_id,
            'activo' => '1',
            'anio_matricula' => now()->year,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($estudiante->refresh()->matriculaVigente());
    }

    public function test_management_can_view_students_but_not_modify_them(): void
    {
        $direccion = User::factory()->direccion()->create();
        $estudiante = Estudiante::factory()->create();

        $this->actingAs($direccion)->get(route('estudiantes.index'))->assertOk();
        $this->actingAs($direccion)->get(route('estudiantes.show', $estudiante))->assertOk();
        $this->actingAs($direccion)->get(route('estudiantes.create'))->assertForbidden();
        $this->actingAs($direccion)->delete(route('estudiantes.destroy', $estudiante))->assertForbidden();
    }

    public function test_guards_can_not_see_the_student_list(): void
    {
        $this->actingAs(User::factory()->guarda()->create())->get(route('estudiantes.index'))->assertForbidden();
    }
}
