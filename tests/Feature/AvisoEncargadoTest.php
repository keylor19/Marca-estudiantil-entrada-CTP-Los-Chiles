<?php

namespace Tests\Feature;

use App\Jobs\EnviarAvisoEncargado;
use App\Mail\AvisoAsistenciaMail;
use App\Models\Asistencia;
use App\Models\Aviso;
use App\Models\Configuracion;
use App\Models\Estudiante;
use App\Models\User;
use App\Services\ClienteWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AvisoEncargadoTest extends TestCase
{
    use RefreshDatabase;

    private User $guarda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setTime(6, 45));
        $this->guarda = User::factory()->guarda()->create();

        Configuracion::actual()->update([
            'avisar_por_whatsapp' => true,
            'avisar_por_correo' => true,
            'avisar_entrada' => true,
            'avisar_salida' => true,
            'registrar_salida' => true,
        ]);

        config([
            'services.whatsapp.token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.plantilla' => 'aviso_asistencia',
        ]);
    }

    public function test_an_entry_queues_a_whatsapp_and_an_email_notice(): void
    {
        Queue::fake();
        $estudiante = Estudiante::factory()->create();

        $this->escanear($estudiante);

        Queue::assertPushed(EnviarAvisoEncargado::class, 2);
        Queue::assertPushed(EnviarAvisoEncargado::class, fn ($job) => $job->tipo === 'entrada' && $job->canal === 'whatsapp');
        Queue::assertPushed(EnviarAvisoEncargado::class, fn ($job) => $job->tipo === 'entrada' && $job->canal === 'correo');
    }

    public function test_an_exit_queues_exit_notices(): void
    {
        $estudiante = Estudiante::factory()->create();
        Asistencia::factory()->for($estudiante)->create();
        Queue::fake();

        $this->escanear($estudiante, 'salida');

        Queue::assertPushed(EnviarAvisoEncargado::class, 2);
        Queue::assertPushed(EnviarAvisoEncargado::class, fn ($job) => $job->tipo === 'salida');
    }

    public function test_a_duplicate_scan_does_not_notify_again(): void
    {
        $estudiante = Estudiante::factory()->create();
        Asistencia::factory()->for($estudiante)->create();
        Queue::fake();

        $this->escanear($estudiante);

        Queue::assertNothingPushed();
    }

    public function test_only_the_enabled_channel_is_used(): void
    {
        Configuracion::actual()->update(['avisar_por_correo' => false]);
        Queue::fake();

        $this->escanear(Estudiante::factory()->create());

        Queue::assertPushed(EnviarAvisoEncargado::class, 1);
        Queue::assertPushed(EnviarAvisoEncargado::class, fn ($job) => $job->canal === 'whatsapp');
    }

    public function test_entries_are_not_notified_when_entry_notices_are_off(): void
    {
        Configuracion::actual()->update(['avisar_entrada' => false]);
        Queue::fake();

        $this->escanear(Estudiante::factory()->create());

        Queue::assertNothingPushed();
    }

    public function test_students_without_guardian_contact_or_with_notices_off_are_not_notified(): void
    {
        Queue::fake();

        $this->escanear(Estudiante::factory()->sinEncargado()->create());
        $this->escanear(Estudiante::factory()->create(['avisar_encargado' => false]));

        Queue::assertNothingPushed();
    }

    public function test_the_whatsapp_job_sends_the_approved_template_and_logs_it(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        $estudiante = Estudiante::factory()->create(['nombre' => 'Ana', 'apellido1' => 'Mora', 'apellido2' => 'Vega', 'encargado_telefono' => '8888-7777']);
        $asistencia = Asistencia::factory()->for($estudiante)->tarde()->create();

        (new EnviarAvisoEncargado($asistencia->id, 'entrada', 'whatsapp'))->handle(app(ClienteWhatsApp::class));

        Http::assertSent(function (Request $request) use ($asistencia) {
            return $request->url() === 'https://graph.facebook.com/v21.0/123456/messages'
                && $request->hasHeader('Authorization', 'Bearer token-de-prueba')
                && $request['to'] === '50688887777'
                && $request['template']['name'] === 'aviso_asistencia'
                && array_column($request['template']['components'][0]['parameters'], 'text') === [
                    'Ana Mora Vega', 'entrada (llegada tardía)', $asistencia->fecha->format('d/m/Y'), '07:25:00 AM',
                ];
        });

        $this->assertDatabaseHas('avisos', ['estudiante_id' => $estudiante->id, 'canal' => 'whatsapp', 'destino' => '50688887777', 'estado' => Aviso::ENVIADO]);
    }

    public function test_a_rejected_whatsapp_message_is_logged_as_failed_with_the_reason(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template name does not exist']], 400)]);
        $asistencia = Asistencia::factory()->create();
        $job = new EnviarAvisoEncargado($asistencia->id, 'entrada', 'whatsapp');

        try {
            $job->handle(app(ClienteWhatsApp::class));
            $this->fail('Se esperaba un error de WhatsApp.');
        } catch (RuntimeException $error) {
            $job->failed($error);
        }

        $aviso = Aviso::sole();
        $this->assertSame(Aviso::FALLIDO, $aviso->estado);
        $this->assertStringContainsString('Template name does not exist', $aviso->error);
    }

    public function test_whatsapp_without_credentials_explains_what_is_missing(): void
    {
        config(['services.whatsapp.token' => null]);

        $this->expectExceptionMessage('WHATSAPP_TOKEN');

        app(ClienteWhatsApp::class)->enviarPlantilla('50688887777', ['a', 'b', 'c', 'd']);
    }

    public function test_the_email_job_sends_the_notice_to_the_guardian(): void
    {
        Mail::fake();
        $estudiante = Estudiante::factory()->create(['encargado_correo' => 'madre@correo.com', 'encargado_nombre' => 'Laura Vega']);
        $asistencia = Asistencia::factory()->for($estudiante)->create(['hora_salida' => '16:05:00']);

        (new EnviarAvisoEncargado($asistencia->id, 'salida', 'correo'))->handle(app(ClienteWhatsApp::class));

        Mail::assertSent(AvisoAsistenciaMail::class, function (AvisoAsistenciaMail $correo) use ($estudiante) {
            return $correo->hasTo('madre@correo.com')
                && $correo->datos['accion'] === 'salida'
                && $correo->datos['hora'] === '04:05:00 PM'
                && str_contains($correo->envelope()->subject, $estudiante->nombreCompleto());
        });
        $this->assertDatabaseHas('avisos', ['canal' => 'correo', 'destino' => 'madre@correo.com', 'estado' => Aviso::ENVIADO]);
    }

    public function test_the_email_names_the_guardian_student_time_and_school(): void
    {
        $html = (new AvisoAsistenciaMail([
            'colegio' => 'CTP Los Chiles', 'encargado' => 'Laura Vega', 'estudiante' => 'Ana Mora Vega',
            'seccion' => '10-2 A', 'accion' => 'entrada', 'fecha' => '24/09/2026', 'hora' => '06:45:00 AM',
        ]))->render();

        foreach (['Laura Vega', 'Ana Mora Vega', '10-2 A', '06:45:00 AM', 'CTP Los Chiles'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function telefonos(): array
    {
        return [
            'con guion' => ['8888-7777', '50688887777'],
            'con código de país' => ['+506 8888 7777', '50688887777'],
            'extranjero' => ['+1 (305) 555-1234', '13055551234'],
            'incompleto' => ['8888', null],
        ];
    }

    #[DataProvider('telefonos')]
    public function test_phone_numbers_are_converted_to_whatsapp_format(string $telefono, ?string $esperado): void
    {
        $this->assertSame($esperado, (new Estudiante(['encargado_telefono' => $telefono]))->telefonoWhatsApp());
    }

    public function test_the_admin_saves_the_guardian_data_in_the_student_form(): void
    {
        $estudiante = Estudiante::factory()->sinEncargado()->create();

        $this->actingAs(User::factory()->create())->put(route('estudiantes.update', $estudiante), [
            'cedula' => $estudiante->cedula,
            'nombre' => $estudiante->nombre,
            'apellido1' => $estudiante->apellido1,
            'subgrupo_id' => $estudiante->subgrupo_id,
            'activo' => '1',
            'encargado_nombre' => 'Laura Vega',
            'encargado_parentesco' => 'Madre',
            'encargado_telefono' => '8888-7777',
            'encargado_correo' => 'laura@correo.com',
            'avisar_encargado' => '1',
        ])->assertSessionHasNoErrors();

        $estudiante->refresh();
        $this->assertSame('Laura Vega', $estudiante->encargado_nombre);
        $this->assertSame('50688887777', $estudiante->telefonoWhatsApp());
        $this->assertSame('laura@correo.com', $estudiante->encargado_correo);
    }

    public function test_an_invalid_guardian_phone_or_email_is_rejected(): void
    {
        $estudiante = Estudiante::factory()->create();

        $this->actingAs(User::factory()->create())->put(route('estudiantes.update', $estudiante), [
            'cedula' => $estudiante->cedula,
            'nombre' => $estudiante->nombre,
            'apellido1' => $estudiante->apellido1,
            'subgrupo_id' => $estudiante->subgrupo_id,
            'encargado_telefono' => 'abc',
            'encargado_correo' => 'no-es-correo',
        ])->assertSessionHasErrors(['encargado_telefono', 'encargado_correo']);
    }

    public function test_the_import_loads_guardian_columns(): void
    {
        $csv = "cedula;nombre;apellido1;seccion;encargado;parentesco;telefono_encargado;correo_encargado\n"
            ."1-1111-1111;Luis;Vargas;8-1;Rosa Vargas;Madre;8765-4321;rosa@correo.com\n";

        $this->actingAs(User::factory()->create())->post(route('estudiantes.importar.store'), [
            'archivo' => UploadedFile::fake()->createWithContent('e.csv', $csv),
        ]);

        $this->assertDatabaseHas('estudiantes', [
            'cedula' => '1-1111-1111',
            'encargado_nombre' => 'Rosa Vargas',
            'encargado_parentesco' => 'Madre',
            'encargado_telefono' => '8765-4321',
            'encargado_correo' => 'rosa@correo.com',
        ]);
    }

    public function test_the_admin_can_send_a_test_whatsapp_from_the_configuration(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $this->actingAs(User::factory()->create())
            ->post(route('configuracion.probar-avisos'), ['telefono_prueba' => '8888-7777'])
            ->assertSessionHas('resultado_prueba', ['✓ WhatsApp enviado a +50688887777.']);

        Http::assertSent(fn (Request $request) => $request['to'] === '50688887777');
    }

    public function test_the_configuration_page_shows_the_notice_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('configuracion.edit'))
            ->assertOk()
            ->assertSee('Avisos al encargado legal')
            ->assertSee('WhatsApp configurado');
    }

    private function escanear(Estudiante $estudiante, string $modo = 'entrada'): void
    {
        $this->actingAs($this->guarda)
            ->postJson(route('escaner.registrar'), ['codigo' => $estudiante->codigo_barra, 'modo' => $modo])
            ->assertOk();
    }
}
