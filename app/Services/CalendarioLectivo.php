<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\DiaNoLectivo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class CalendarioLectivo
{
    /**
     * Días lectivos (Y-m-d) del rango: solo los días de la semana configurados, sin feriados
     * ni días no lectivos, y nunca posteriores a hoy (un día futuro no puede ser ausencia).
     *
     * @return list<string>
     */
    public function diasLectivos(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $desde = CarbonImmutable::parse($desde)->startOfDay();
        $hasta = CarbonImmutable::parse($hasta)->startOfDay()->min(CarbonImmutable::today());

        if ($hasta->lt($desde)) {
            return [];
        }

        $diasSemana = Configuracion::actual()->dias_lectivos;
        $noLectivos = DiaNoLectivo::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get()
            ->map(fn (DiaNoLectivo $dia) => $dia->fecha->toDateString())
            ->flip();

        $dias = [];

        foreach (CarbonPeriod::create($desde, $hasta) as $dia) {
            if (in_array($dia->dayOfWeekIso, $diasSemana) && ! $noLectivos->has($dia->toDateString())) {
                $dias[] = $dia->toDateString();
            }
        }

        return $dias;
    }

    public function esLectivo(CarbonInterface $fecha): bool
    {
        return in_array($fecha->toDateString(), $this->diasLectivos($fecha, $fecha), true);
    }
}
