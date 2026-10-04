<?php

namespace App\Http\Controllers;

use App\Mail\AvisoAsistenciaMail;
use App\Models\Aviso;
use App\Models\Configuracion;
use App\Models\DiaNoLectivo;
use App\Models\Estudiante;
use App\Services\AvisoEncargado;
use App\Services\ClienteWhatsApp;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ConfiguracionController extends Controller
{
    public function edit(ClienteWhatsApp $whatsApp): View
    {
        return view('configuracion.edit', [
            'configuracion' => Configuracion::actual(),
            'whatsAppConfigurado' => $whatsApp->configurado(),
            'correoConfigurado' => ! in_array(config('mail.default'), ['log', 'array'], true),
            'avisosFallidosHoy' => Aviso::query()->where('estado', Aviso::FALLIDO)->whereDate('created_at', today())->count(),
            'diasNoLectivos' => DiaNoLectivo::query()
                ->where('fecha', '>=', today()->startOfYear()->toDateString())
                ->orderBy('fecha')
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        foreach (['registrar_salida', 'avisar_por_whatsapp', 'avisar_por_correo', 'avisar_entrada', 'avisar_salida'] as $opcion) {
            $request->merge([$opcion => $request->boolean($opcion)]);
        }

        $datos = $request->validate([
            'nombre_colegio' => ['required', 'string', 'max:150'],
            'hora_entrada' => ['required', 'date_format:H:i'],
            'minutos_tolerancia' => ['required', 'integer', 'min:0', 'max:240'],
            'registrar_salida' => ['boolean'],
            'dias_lectivos' => ['required', 'array', 'min:1'],
            'dias_lectivos.*' => ['integer', 'between:1,7'],
            'segundos_pantalla' => ['required', 'integer', 'min:2', 'max:30'],
            'avisar_por_whatsapp' => ['boolean'],
            'avisar_por_correo' => ['boolean'],
            'avisar_entrada' => ['boolean'],
            'avisar_salida' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $configuracion = Configuracion::actual();
        $datos['dias_lectivos'] = array_map('intval', $datos['dias_lectivos']);
        $datos['hora_entrada'] .= ':00';

        if ($request->hasFile('logo')) {
            if ($configuracion->logo) {
                Storage::disk('local')->delete($configuracion->logo);
            }

            $datos['logo'] = $request->file('logo')->store('logo', 'local');
        }

        $configuracion->update($datos);

        return redirect()->route('configuracion.edit')->with('status', 'Configuración guardada.');
    }

    /**
     * Envía en el momento (sin cola) un aviso de prueba para verificar la configuración de WhatsApp y del correo.
     */
    public function probarAvisos(Request $request, ClienteWhatsApp $whatsApp): RedirectResponse
    {
        $datos = $request->validate([
            'telefono_prueba' => ['nullable', 'required_without:correo_prueba', 'string', 'regex:/^\+?[\d\s\-()]{8,20}$/'],
            'correo_prueba' => ['nullable', 'required_without:telefono_prueba', 'email'],
        ], [
            'telefono_prueba.required_without' => 'Indique un teléfono o un correo para la prueba.',
            'correo_prueba.required_without' => 'Indique un teléfono o un correo para la prueba.',
        ]);

        $ejemplo = [
            'colegio' => Configuracion::actual()->nombre_colegio,
            'encargado' => 'encargado(a)',
            'estudiante' => 'ESTUDIANTE DE PRUEBA',
            'seccion' => '10-1 A',
            'accion' => 'entrada',
            'fecha' => today()->format('d/m/Y'),
            'hora' => now()->format('h:i A'),
        ];

        $resultados = [];

        if (filled($datos['telefono_prueba'] ?? null)) {
            $telefono = (new Estudiante(['encargado_telefono' => $datos['telefono_prueba']]))->telefonoWhatsApp();

            try {
                $whatsApp->enviarPlantilla((string) $telefono, AvisoEncargado::variablesWhatsApp($ejemplo));
                $resultados[] = "✓ WhatsApp enviado a +{$telefono}.";
            } catch (Throwable $error) {
                $resultados[] = '✕ WhatsApp: '.$error->getMessage();
            }
        }

        if (filled($datos['correo_prueba'] ?? null)) {
            try {
                Mail::to($datos['correo_prueba'])->send(new AvisoAsistenciaMail($ejemplo));
                $resultados[] = in_array(config('mail.default'), ['log', 'array'], true)
                    ? '⚠ El correo NO salió: el sistema está en modo de prueba (MAIL_MAILER=log). Configure el servidor de correo en el archivo .env.'
                    : "✓ Correo enviado a {$datos['correo_prueba']}.";
            } catch (Throwable $error) {
                $resultados[] = '✕ Correo: '.$error->getMessage();
            }
        }

        return redirect()->route('configuracion.edit')->withInput()->with('resultado_prueba', $resultados);
    }

    /**
     * Registra un feriado o un período sin lecciones (ej. vacaciones de medio año) para no contarlo como ausencia.
     */
    public function guardarDiaNoLectivo(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha'],
            'descripcion' => ['required', 'string', 'max:150'],
        ]);

        $periodo = CarbonPeriod::create($datos['fecha'], $datos['fecha_fin'] ?? $datos['fecha']);

        if ($periodo->count() > 366) {
            return back()->withInput()->withErrors(['fecha_fin' => 'El período no puede ser mayor a un año.']);
        }

        foreach ($periodo as $dia) {
            DiaNoLectivo::query()->updateOrCreate(
                ['fecha' => $dia->toDateString()],
                ['descripcion' => $datos['descripcion']],
            );
        }

        return redirect()->route('configuracion.edit')->with('status', 'Día(s) no lectivo(s) registrado(s).');
    }

    public function eliminarDiaNoLectivo(DiaNoLectivo $diaNoLectivo): RedirectResponse
    {
        $diaNoLectivo->delete();

        return redirect()->route('configuracion.edit')->with('status', 'Día no lectivo eliminado.');
    }

    /**
     * Logo del colegio (público, se muestra en la pantalla de inicio de sesión).
     */
    public function logo(): Response
    {
        $logo = Configuracion::actual()->logo;

        abort_unless($logo && Storage::disk('local')->exists($logo), 404);

        return Storage::disk('local')->response($logo, null, ['Cache-Control' => 'public, max-age=3600']);
    }
}
