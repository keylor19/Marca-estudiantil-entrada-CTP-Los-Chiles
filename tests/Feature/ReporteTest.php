<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\DiaNoLectivo;
use App\Models\Estudiante;
use App\Models\Subgrupo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Semana del lunes 14 al viernes 18 de setiembre de 2026, con el miércoles 16 como feriado:
 * 4 días lectivos (14, 15, 17 y 18).
 */
class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private User $direccion;

    private Subgrupo $subgrupo;

    private Estudiante $cumplido;

    private Estudiante $ausente;

    private array $periodo = ['desde' => '2026-09-14', 'hasta' => '2026-09-18'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-21 10:00:00');
        $this->direccion = User::factory()->direccion()->create();
        $this->subgrupo = Subgrupo::factory()->create();

        DiaNoLectivo::create(['fecha' => '2026-09-16', 'descripcion' => 'Feriado']);

        $this->cumplido = Estudiante::factory()->for($this->subgrupo)->create(['apellido1' => 'Alfaro']);
        Asistencia::factory()->for($this->cumplido)->create(['fecha' => '2026-09-14']);
        Asistencia::factory()->for($this->cumplido)->tarde()->create(['fecha' => '2026-09-15']);
        Asistencia::factory()->for($this->cumplido)->create(['fecha' => '2026-09-16']); // feriado: no cuenta
        Asistencia::factory()->for($this->cumplido)->create(['fecha' => '2026-09-18']);

        $this->ausente = Estudiante::factory()->for($this->subgrupo)->create(['apellido1' => 'Zúñiga']);
    }

    public function test_group_report_counts_attendance_lates_and_absences_only_on_school_days(): void
    {
        $filas = $this->reporteGrupo(['subgrupo_id' => $this->subgrupo->id]);

        $this->assertSame(4, $filas[0]['lectivos']);
        $this->assertSame(
            ['presentes' => 3, 'tardias' => 1, 'ausencias' => 1, 'porcentaje' => 75.0],
            array_intersect_key($filas[0], array_flip(['presentes', 'tardias', 'ausencias', 'porcentaje'])),
        );
        $this->assertSame(
            ['presentes' => 0, 'tardias' => 0, 'ausencias' => 4, 'porcentaje' => 0.0],
            array_intersect_key($filas[1], array_flip(['presentes', 'tardias', 'ausencias', 'porcentaje'])),
        );
    }

    public function test_group_report_by_section_excludes_other_sections(): void
    {
        Estudiante::factory()->create();

        $filas = $this->reporteGrupo(['seccion_id' => $this->subgrupo->seccion_id]);

        $this->assertEquals([$this->cumplido->id, $this->ausente->id], $filas->pluck('estudiante.id')->all());
    }

    public function test_inactive_students_appear_only_when_they_have_marks_in_the_period(): void
    {
        $retiradoConMarcas = Estudiante::factory()->for($this->subgrupo)->inactivo()->create(['apellido1' => 'Mora']);
        Asistencia::factory()->for($retiradoConMarcas)->create(['fecha' => '2026-09-14']);
        Estudiante::factory()->for($this->subgrupo)->inactivo()->create();

        $filas = $this->reporteGrupo(['subgrupo_id' => $this->subgrupo->id]);

        $this->assertEquals([$this->cumplido->id, $retiradoConMarcas->id, $this->ausente->id], $filas->pluck('estudiante.id')->all());
    }

    public function test_future_days_are_not_counted_as_absences(): void
    {
        $filas = $this->reporteGrupo(['subgrupo_id' => $this->subgrupo->id, 'hasta' => '2026-12-31']);

        // Del 14 al 21 (hoy): 14, 15, 17, 18 y el lunes 21.
        $this->assertSame(5, $filas[1]['lectivos']);
    }

    public function test_student_report_lists_each_school_day_and_the_holiday_with_a_mark(): void
    {
        $response = $this->actingAs($this->direccion)
            ->get(route('reportes.estudiante', ['cedula' => $this->cumplido->cedula, ...$this->periodo]))
            ->assertOk()
            ->assertSee($this->cumplido->nombreCompleto());

        $reporte = $response->viewData('reporte');

        $this->assertSame(
            ['2026-09-14' => 'Presente', '2026-09-15' => 'Tardía', '2026-09-16' => 'Presente', '2026-09-17' => 'Ausente', '2026-09-18' => 'Presente'],
            collect($reporte['dias'])->mapWithKeys(fn (array $dia) => [$dia['fecha']->toDateString() => $dia['estado']])->all(),
        );
        $this->assertFalse($reporte['dias'][2]['lectivo']);
        $this->assertSame(1, $reporte['resumen']['ausencias']);
    }

    public function test_student_report_shows_a_message_when_the_id_number_does_not_exist(): void
    {
        $this->actingAs($this->direccion)
            ->get(route('reportes.estudiante', ['cedula' => '9-9999-9999']))
            ->assertOk()
            ->assertSee('No se encontró ningún estudiante');
    }

    public function test_daily_list_shows_who_is_present_late_and_absent(): void
    {
        $totales = $this->actingAs($this->direccion)
            ->get(route('reportes.diario', ['fecha' => '2026-09-15', 'seccion_id' => $this->subgrupo->seccion_id]))
            ->assertOk()
            ->viewData('reporte')['totales'];

        $this->assertSame(['estudiantes' => 2, 'presentes' => 1, 'tardias' => 1, 'ausentes' => 1], $totales);
    }

    public function test_sections_comparison_aggregates_each_section(): void
    {
        $fila = $this->actingAs($this->direccion)
            ->get(route('reportes.secciones', $this->periodo))
            ->assertOk()
            ->viewData('reporte')['filas']
            ->firstWhere('seccion.id', $this->subgrupo->seccion_id);

        $this->assertSame(2, $fila['estudiantes']);
        $this->assertSame(3, $fila['presentes']);
        $this->assertSame(5, $fila['ausencias']);
        $this->assertSame(37.5, $fila['porcentaje']);
    }

    public function test_reports_can_be_downloaded_as_excel(): void
    {
        $this->actingAs($this->direccion)
            ->get(route('reportes.grupo', ['seccion_id' => $this->subgrupo->seccion_id, 'formato' => 'excel', ...$this->periodo]))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($this->direccion)
            ->get(route('reportes.estudiante', ['cedula' => $this->cumplido->cedula, 'formato' => 'excel', ...$this->periodo]))
            ->assertDownload("asistencia_{$this->cumplido->cedula}.xlsx");
    }

    public function test_the_end_date_can_not_be_before_the_start_date(): void
    {
        $this->actingAs($this->direccion)
            ->get(route('reportes.grupo', ['desde' => '2026-09-18', 'hasta' => '2026-09-14']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_guards_can_not_see_reports(): void
    {
        $this->actingAs(User::factory()->guarda()->create())
            ->get(route('reportes.grupo'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    private function reporteGrupo(array $filtros): Collection
    {
        return $this->actingAs($this->direccion)
            ->get(route('reportes.grupo', [...$this->periodo, ...$filtros]))
            ->assertOk()
            ->viewData('reporte')['filas'];
    }
}
