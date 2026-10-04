<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Subgrupo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarnetTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_cards_include_name_id_section_and_barcode_of_active_students_only(): void
    {
        $subgrupo = Subgrupo::factory()->create();
        $activo = Estudiante::factory()->for($subgrupo)->create();
        $inactivo = Estudiante::factory()->for($subgrupo)->inactivo()->create();
        $deOtraSeccion = Estudiante::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('carnets.imprimir', ['seccion_id' => $subgrupo->seccion_id]))
            ->assertOk()
            ->assertSee($activo->nombreCompleto())
            ->assertSee($activo->cedula)
            ->assertSee($activo->seccionNombre())
            ->assertSee('aria-label="Código de barras '.$activo->codigo_barra.'"', false)
            ->assertDontSee($inactivo->cedula)
            ->assertDontSee($deOtraSeccion->cedula);
    }

    public function test_without_filters_no_cards_are_generated(): void
    {
        Estudiante::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('carnets.imprimir'))
            ->assertOk()
            ->assertViewHas('estudiantes', fn ($estudiantes) => $estudiantes->isEmpty());
    }

    public function test_cards_are_split_into_sheets_by_the_chosen_amount_per_page(): void
    {
        Estudiante::factory()->count(5)->for(Subgrupo::factory())->create();

        $this->actingAs(User::factory()->create())
            ->get(route('carnets.imprimir', ['todos' => 1, 'por_hoja' => 2]))
            ->assertOk()
            ->assertViewHas('porHoja', 2)
            ->assertViewHas('hojas', fn ($hojas) => $hojas->map->count()->values()->all() === [2, 2, 1]);
    }

    public function test_nine_cards_per_page_is_the_default_and_unsupported_amounts_are_rejected(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('carnets.imprimir', ['todos' => 1]))
            ->assertViewHas('porHoja', 9);

        $this->actingAs($usuario)
            ->get(route('carnets.imprimir', ['todos' => 1, 'por_hoja' => 12]))
            ->assertSessionHasErrors('por_hoja');
    }

    public function test_only_administrators_can_print_cards(): void
    {
        $this->actingAs(User::factory()->direccion()->create())
            ->get(route('carnets.imprimir', ['todos' => 1]))
            ->assertForbidden();
    }
}
