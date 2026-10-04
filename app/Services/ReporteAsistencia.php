<?php

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Seccion;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReporteAsistencia
{
    public function __construct(private CalendarioLectivo $calendario) {}

    /**
     * Detalle día por día de un estudiante. Incluye los días lectivos (presente, tardía o ausente)
     * y cualquier día no lectivo en el que el estudiante haya marcado.
     *
     * @return array{dias: list<array{fecha: Carbon, lectivo: bool, asistencia: Asistencia|null, estado: string}>, resumen: array{lectivos: int, presentes: int, tardias: int, ausencias: int, porcentaje: float}}
     */
    public function porEstudiante(Estudiante $estudiante, CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $lectivos = array_flip($this->calendario->diasLectivos($desde, $hasta));

        $asistencias = $estudiante->asistencias()
            ->with(['usuario', 'usuarioSalida'])
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get()
            ->keyBy(fn (Asistencia $asistencia) => $asistencia->fecha->toDateString());

        $dias = [];

        foreach (CarbonPeriod::create($desde, $hasta) as $dia) {
            $fecha = $dia->toDateString();
            $esLectivo = isset($lectivos[$fecha]);
            $asistencia = $asistencias->get($fecha);

            if (! $esLectivo && ! $asistencia) {
                continue;
            }

            $dias[] = [
                'fecha' => Carbon::parse($fecha),
                'lectivo' => $esLectivo,
                'asistencia' => $asistencia,
                'estado' => $asistencia ? $asistencia->estado->etiqueta() : 'Ausente',
            ];
        }

        return [
            'dias' => $dias,
            'resumen' => $this->resumen(
                count($lectivos),
                $asistencias->filter(fn (Asistencia $a, string $fecha) => isset($lectivos[$fecha]))->count(),
                $asistencias->filter(fn (Asistencia $a, string $fecha) => isset($lectivos[$fecha]) && $a->estado === EstadoAsistencia::Tarde)->count(),
            ),
        ];
    }

    /**
     * Totales por estudiante (asistencias, tardías, ausencias) para una sección, un subgrupo o todo el colegio.
     *
     * @return array{lectivos: int, filas: Collection<int, array{estudiante: Estudiante, lectivos: int, presentes: int, tardias: int, ausencias: int, porcentaje: float}>, totales: array{lectivos: int, presentes: int, tardias: int, ausencias: int, porcentaje: float}}
     */
    public function porGrupo(?int $seccionId, ?int $subgrupoId, CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $lectivos = $this->calendario->diasLectivos($desde, $hasta);

        $estudiantes = $this->estudiantesDelGrupo($seccionId, $subgrupoId, $lectivos)->get();

        $conteos = $this->conteosPorEstudiante($estudiantes->pluck('id')->all(), $lectivos);

        $filas = $estudiantes->map(function (Estudiante $estudiante) use ($conteos, $lectivos) {
            $conteo = $conteos->get($estudiante->id);

            return ['estudiante' => $estudiante, ...$this->resumen(count($lectivos), (int) ($conteo->asistencias ?? 0), (int) ($conteo->tardias ?? 0))];
        });

        return [
            'lectivos' => count($lectivos),
            'filas' => $filas,
            'totales' => $this->resumen(
                count($lectivos) * $filas->count(),
                $filas->sum('presentes'),
                $filas->sum('tardias'),
            ),
        ];
    }

    /**
     * Comparativo de todas las secciones en el período.
     *
     * @return array{lectivos: int, filas: Collection<int, array{seccion: Seccion, estudiantes: int, lectivos: int, presentes: int, tardias: int, ausencias: int, porcentaje: float}>}
     */
    public function porSecciones(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $lectivos = $this->calendario->diasLectivos($desde, $hasta);

        $filas = Seccion::ordenadas()->map(function (Seccion $seccion) use ($lectivos) {
            $ids = $this->estudiantesDelGrupo($seccion->id, null, $lectivos)->pluck('estudiantes.id')->all();
            $conteos = $this->conteosPorEstudiante($ids, $lectivos);

            return [
                'seccion' => $seccion,
                'estudiantes' => count($ids),
                ...$this->resumen(count($lectivos) * count($ids), (int) $conteos->sum('asistencias'), (int) $conteos->sum('tardias')),
            ];
        })->filter(fn (array $fila) => $fila['estudiantes'] > 0)->values();

        return ['lectivos' => count($lectivos), 'filas' => $filas];
    }

    /**
     * Lista de un día: cada estudiante con su hora de entrada/salida o ausente.
     *
     * @return array{lectivo: bool, filas: Collection<int, array{estudiante: Estudiante, asistencia: Asistencia|null}>, totales: array{estudiantes: int, presentes: int, tardias: int, ausentes: int}}
     */
    public function diario(CarbonInterface $fecha, ?int $seccionId, ?int $subgrupoId): array
    {
        $estudiantes = $this->estudiantesDelGrupo($seccionId, $subgrupoId, [$fecha->toDateString()])->get();

        $asistencias = Asistencia::query()
            ->with('usuario')
            ->where('fecha', $fecha->toDateString())
            ->whereIn('estudiante_id', $estudiantes->pluck('id'))
            ->get()
            ->keyBy('estudiante_id');

        $filas = $estudiantes->map(fn (Estudiante $estudiante) => [
            'estudiante' => $estudiante,
            'asistencia' => $asistencias->get($estudiante->id),
        ]);

        return [
            'lectivo' => $this->calendario->esLectivo($fecha),
            'filas' => $filas,
            'totales' => [
                'estudiantes' => $filas->count(),
                'presentes' => $asistencias->count(),
                'tardias' => $asistencias->where('estado', EstadoAsistencia::Tarde)->count(),
                'ausentes' => $filas->count() - $asistencias->count(),
            ],
        ];
    }

    /**
     * Estudiantes activos del grupo, más los inactivos que tengan marcas en las fechas indicadas
     * (para que el historial de un estudiante retirado no desaparezca de los informes).
     *
     * @param  list<string>  $fechas
     * @return Builder<Estudiante>
     */
    private function estudiantesDelGrupo(?int $seccionId, ?int $subgrupoId, array $fechas): Builder
    {
        return Estudiante::query()
            ->with('subgrupo.seccion')
            ->when($subgrupoId, fn (Builder $query) => $query->where('subgrupo_id', $subgrupoId))
            ->when($seccionId && ! $subgrupoId, fn (Builder $query) => $query->deSeccion($seccionId))
            ->where(fn (Builder $query) => $query
                ->where('activo', true)
                ->orWhereHas('asistencias', fn (Builder $asistencias) => $asistencias->whereIn('fecha', $fechas)))
            ->ordenAlfabetico();
    }

    /**
     * @param  list<int>  $estudianteIds
     * @param  list<string>  $lectivos
     * @return Collection<int, object{estudiante_id: int, asistencias: int, tardias: int}>
     */
    private function conteosPorEstudiante(array $estudianteIds, array $lectivos): Collection
    {
        if ($estudianteIds === [] || $lectivos === []) {
            return collect();
        }

        return Asistencia::query()
            ->toBase()
            ->selectRaw('estudiante_id, COUNT(*) as asistencias, SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as tardias', [EstadoAsistencia::Tarde->value])
            ->whereIn('estudiante_id', $estudianteIds)
            ->whereIn('fecha', $lectivos)
            ->groupBy('estudiante_id')
            ->get()
            ->keyBy('estudiante_id');
    }

    /**
     * @return array{lectivos: int, presentes: int, tardias: int, ausencias: int, porcentaje: float}
     */
    private function resumen(int $lectivos, int $presentes, int $tardias): array
    {
        return [
            'lectivos' => $lectivos,
            'presentes' => $presentes,
            'tardias' => $tardias,
            'ausencias' => max($lectivos - $presentes, 0),
            'porcentaje' => $lectivos > 0 ? round($presentes / $lectivos * 100, 1) : 0.0,
        ];
    }
}
