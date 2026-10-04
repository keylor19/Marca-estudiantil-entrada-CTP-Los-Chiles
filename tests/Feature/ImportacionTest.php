<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

class ImportacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_a_spanish_excel_csv_creates_students_sections_and_subgroups(): void
    {
        $csv = "Cédula;Nombre;Primer apellido;Segundo apellido;Sección;Subgrupo;Especialidad;Fecha nacimiento\n"
            ."2-0876-0123;María José;Rodríguez;Pérez;10-2;A;Contabilidad;15/03/2010\n"
            ."2-0880-0456;Carlos;Soto;;10-2;B;Informática;\n"
            ."2-0880-0999;Ana;Mora;Vega;7 - 1;;;\n";

        $this->importar($csv)->assertSessionHas('resultado', ['creados' => 3, 'actualizados' => 0, 'errores' => []]);

        $maria = Estudiante::query()->where('cedula', '2-0876-0123')->sole();
        $this->assertSame('10-2 A', $maria->seccionNombre());
        $this->assertSame('Contabilidad', $maria->subgrupo->especialidad);
        $this->assertSame('2010-03-15', $maria->fecha_nacimiento->toDateString());
        $this->assertNotNull($maria->codigo_barra);

        $this->assertSame('7-1 A', Estudiante::query()->where('cedula', '2-0880-0999')->sole()->seccionNombre());
        $this->assertSame('Décimo', Seccion::query()->where('nombre', '10-2')->sole()->nivel);
        $this->assertSame(2, Seccion::count());
    }

    public function test_importing_again_updates_by_id_number_without_duplicating(): void
    {
        $this->importar("cedula,nombre,apellido1,seccion\n1-1111-1111,Luis,Vargas,8-1\n");
        $codigo = Estudiante::sole()->codigo_barra;

        $this->importar("cedula,nombre,apellido1,seccion\n1-1111-1111,Luis Diego,Vargas,9-1\n")
            ->assertSessionHas('resultado', ['creados' => 0, 'actualizados' => 1, 'errores' => []]);

        $estudiante = Estudiante::sole();
        $this->assertSame('Luis Diego', $estudiante->nombre);
        $this->assertSame('9-1 A', $estudiante->seccionNombre());
        $this->assertSame($codigo, $estudiante->codigo_barra);
    }

    public function test_importing_the_new_enrollment_renews_matching_students_and_reports_the_missing_ones(): void
    {
        $renovado = Estudiante::factory()->matriculaVencida()->create(['cedula' => '1-1111-1111', 'nombre' => 'Luis']);
        $noMatriculado = Estudiante::factory()->matriculaVencida()->create();

        $this->importar("cedula,nombre,apellido1,seccion\n1-1111-1111,Luis Diego,Vargas,9-1\n")
            ->assertSessionHas('anio_matricula', now()->year)
            ->assertSessionHas('sin_matricula', 1);

        $renovado->refresh();
        $this->assertSame(now()->year, $renovado->anio_matricula);
        $this->assertSame('Luis Diego', $renovado->nombre);
        $this->assertFalse($noMatriculado->refresh()->matriculaVigente());
    }

    public function test_the_enrollment_can_be_imported_for_next_year_but_not_for_a_past_one(): void
    {
        $this->importar("cedula,nombre,apellido1,seccion\n1-1111-1111,Luis,Vargas,8-1\n", now()->year + 1);
        $this->assertSame(now()->year + 1, Estudiante::sole()->anio_matricula);

        $this->importar("cedula,nombre,apellido1,seccion\n1-1111-1111,Luis,Vargas,8-1\n", now()->year - 1)
            ->assertSessionHasErrors('anio_matricula');
    }

    public function test_rows_with_missing_data_are_reported_and_the_rest_imported(): void
    {
        $this->importar("cedula,nombre,apellido1,seccion\n1-1111-1111,Luis,Vargas,8-1\n1-2222-2222,,Soto,8-1\n");

        $resultado = session('resultado');
        $this->assertSame(1, $resultado['creados']);
        $this->assertCount(1, $resultado['errores']);
        $this->assertStringContainsString('Fila 3', $resultado['errores'][0]);
    }

    public function test_a_file_without_the_required_columns_is_rejected(): void
    {
        $this->importar("nombre,apellido\nLuis,Vargas\n");

        $this->assertStringContainsString('Faltan columnas obligatorias', session('resultado')['errores'][0]);
        $this->assertSame(0, Estudiante::count());
    }

    public function test_the_template_downloads_as_excel_and_can_be_imported_back(): void
    {
        $response = $this->actingAs($this->admin)->get(route('estudiantes.plantilla'))->assertDownload('plantilla_estudiantes.xlsx');

        $archivo = new UploadedFile($response->getFile()->getPathname(), 'plantilla.xlsx', null, null, true);
        $this->actingAs($this->admin)->post(route('estudiantes.importar.store'), ['archivo' => $archivo]);

        $this->assertSame(2, Estudiante::count());
    }

    public function test_photos_in_a_zip_are_matched_to_students_by_id_number(): void
    {
        Storage::fake('local');
        $estudiante = Estudiante::factory()->create(['cedula' => '2-0876-0123']);

        $zip = tempnam(sys_get_temp_dir(), 'fotos').'.zip';
        $archivo = new ZipArchive;
        $archivo->open($zip, ZipArchive::CREATE);
        $archivo->addFromString('fotos/208760123.jpg', UploadedFile::fake()->image('a.jpg', 300, 400)->get());
        $archivo->addFromString('fotos/9-9999-9999.jpg', UploadedFile::fake()->image('b.jpg')->get());
        $archivo->close();

        $this->actingAs($this->admin)
            ->post(route('estudiantes.fotos.store'), ['zip' => new UploadedFile($zip, 'fotos.zip', 'application/zip', null, true)])
            ->assertSessionHas('resultado', ['asignadas' => 1, 'sin_estudiante' => ['9-9999-9999.jpg']]);

        Storage::disk('local')->assertExists($estudiante->refresh()->foto);
    }

    private function importar(string $contenido, ?int $anioMatricula = null): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('estudiantes.importar.store'), array_filter([
            'archivo' => UploadedFile::fake()->createWithContent('estudiantes.csv', $contenido),
            'anio_matricula' => $anioMatricula,
        ]));
    }
}
