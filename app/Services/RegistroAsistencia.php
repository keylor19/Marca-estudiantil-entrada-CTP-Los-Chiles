<?php

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class RegistroAsistencia
{
    public const MODO_ENTRADA = 'entrada';

    public const MODO_SALIDA = 'salida';

    public function __construct(private AvisoEncargado $avisos) {}

    /**
     * Registra la lectura de un carnet (o la cédula digitada) y devuelve lo que debe mostrar la pantalla del guarda.
     *
     * @return array{resultado: string, tipo: 'exito'|'advertencia'|'error', titulo: string, mensaje: string, estudiante: array<string, string>|null, hora_entrada: string|null, hora_salida: string|null}
     */
    public function registrar(string $codigo, User $guarda, string $modo = self::MODO_ENTRADA): array
    {
        $estudiante = $this->buscarEstudiante($codigo);

        if (! $estudiante) {
            return $this->respuesta('no_encontrado', 'error', 'CÓDIGO NO ENCONTRADO', 'El estudiante no está registrado. Consulte con administración.');
        }

        if (! $estudiante->activo) {
            return $this->respuesta('inactivo', 'error', 'ESTUDIANTE INACTIVO', 'El carnet pertenece a un estudiante inactivo. Consulte con administración.', $estudiante);
        }

        if (! $estudiante->matriculaVigente()) {
            $matricula = $estudiante->anio_matricula ? "Última matrícula: {$estudiante->anio_matricula}." : 'Sin año de matrícula registrado.';

            return $this->respuesta('matricula_vencida', 'advertencia', 'MATRÍCULA '.Estudiante::cursoLectivo().' NO ACTUALIZADA', "{$matricula} No se registró la marca: administración debe actualizar los datos del estudiante o deshabilitarlo.", $estudiante);
        }

        $ahora = now();
        $asistencia = Asistencia::query()
            ->where('estudiante_id', $estudiante->id)
            ->where('fecha', $ahora->toDateString())
            ->first();

        if ($modo === self::MODO_SALIDA) {
            return $this->registrarSalida($estudiante, $asistencia, $guarda);
        }

        if ($asistencia) {
            return $this->respuesta('duplicado', 'advertencia', 'YA TIENE REGISTRADA SU ENTRADA', 'Entrada registrada a las '.Asistencia::formatoHora($asistencia->hora_entrada).'.', $estudiante, $asistencia);
        }

        $estado = Configuracion::actual()->esTardia($ahora) ? EstadoAsistencia::Tarde : EstadoAsistencia::Presente;

        try {
            $asistencia = Asistencia::create([
                'estudiante_id' => $estudiante->id,
                'fecha' => $ahora->toDateString(),
                'hora_entrada' => $ahora->format('H:i:s'),
                'estado' => $estado,
                'usuario_id' => $guarda->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dos lecturas simultáneas del mismo carnet: la base de datos solo acepta la primera.
            $asistencia = Asistencia::query()->where('estudiante_id', $estudiante->id)->where('fecha', $ahora->toDateString())->first();

            return $this->respuesta('duplicado', 'advertencia', 'YA TIENE REGISTRADA SU ENTRADA', 'Entrada registrada a las '.Asistencia::formatoHora($asistencia?->hora_entrada).'.', $estudiante, $asistencia);
        }

        $this->avisos->programar($asistencia, AvisoEncargado::ENTRADA);

        if ($estado === EstadoAsistencia::Tarde) {
            return $this->respuesta('tarde', 'advertencia', 'ENTRADA REGISTRADA — LLEGADA TARDÍA', 'Hora límite: '.Asistencia::formatoHora(Configuracion::actual()->horaLimite()).'.', $estudiante, $asistencia);
        }

        return $this->respuesta('entrada', 'exito', 'ENTRADA REGISTRADA', 'Bienvenido(a).', $estudiante, $asistencia);
    }

    /**
     * Busca por código de barras o por cédula (con o sin guiones), para cuando el estudiante olvida el carnet.
     */
    public function buscarEstudiante(string $codigo): ?Estudiante
    {
        $codigo = strtoupper(trim($codigo));

        if ($codigo === '') {
            return null;
        }

        $sinGuiones = str_replace(['-', ' '], '', $codigo);

        return Estudiante::query()
            ->with('subgrupo.seccion')
            ->where('codigo_barra', $codigo)
            ->orWhere('cedula', $codigo)
            ->orWhereRaw("REPLACE(REPLACE(cedula, '-', ''), ' ', '') = ?", [$sinGuiones])
            ->first();
    }

    /**
     * @return array{resultado: string, tipo: 'exito'|'advertencia'|'error', titulo: string, mensaje: string, estudiante: array<string, string>|null, hora_entrada: string|null, hora_salida: string|null}
     */
    private function registrarSalida(Estudiante $estudiante, ?Asistencia $asistencia, User $guarda): array
    {
        if (! $asistencia) {
            return $this->respuesta('sin_entrada', 'error', 'SIN ENTRADA REGISTRADA', 'El estudiante no registró entrada hoy.', $estudiante);
        }

        if ($asistencia->hora_salida) {
            return $this->respuesta('salida_duplicada', 'advertencia', 'YA TIENE REGISTRADA SU SALIDA', 'Salida registrada a las '.Asistencia::formatoHora($asistencia->hora_salida).'.', $estudiante, $asistencia);
        }

        $asistencia->update([
            'hora_salida' => now()->format('H:i:s'),
            'usuario_salida_id' => $guarda->id,
        ]);

        $this->avisos->programar($asistencia, AvisoEncargado::SALIDA);

        return $this->respuesta('salida', 'exito', 'SALIDA REGISTRADA', 'Hasta luego.', $estudiante, $asistencia);
    }

    /**
     * @param  'exito'|'advertencia'|'error'  $tipo
     * @return array{resultado: string, tipo: 'exito'|'advertencia'|'error', titulo: string, mensaje: string, estudiante: array<string, string>|null, hora_entrada: string|null, hora_salida: string|null}
     */
    private function respuesta(string $resultado, string $tipo, string $titulo, string $mensaje, ?Estudiante $estudiante = null, ?Asistencia $asistencia = null): array
    {
        return [
            'resultado' => $resultado,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'estudiante' => $estudiante ? [
                'nombre' => $estudiante->nombreCompleto(),
                'cedula' => $estudiante->cedula,
                'seccion' => $estudiante->seccionNombre(),
                'codigo' => $estudiante->codigo_barra,
                'foto' => $estudiante->fotoUrl(),
            ] : null,
            'hora_entrada' => $asistencia ? Asistencia::formatoHora($asistencia->hora_entrada) : null,
            'hora_salida' => $asistencia?->hora_salida ? Asistencia::formatoHora($asistencia->hora_salida) : null,
        ];
    }
}
