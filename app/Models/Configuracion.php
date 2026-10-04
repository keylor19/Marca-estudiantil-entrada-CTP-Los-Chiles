<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'nombre_colegio', 'logo', 'hora_entrada', 'minutos_tolerancia',
        'registrar_salida', 'dias_lectivos', 'segundos_pantalla',
        'avisar_por_whatsapp', 'avisar_por_correo', 'avisar_entrada', 'avisar_salida',
    ];

    protected function casts(): array
    {
        return [
            'registrar_salida' => 'boolean',
            'avisar_por_whatsapp' => 'boolean',
            'avisar_por_correo' => 'boolean',
            'avisar_entrada' => 'boolean',
            'avisar_salida' => 'boolean',
            'dias_lectivos' => 'array',
            'minutos_tolerancia' => 'integer',
            'segundos_pantalla' => 'integer',
        ];
    }

    /**
     * Configuración única del sistema (se crea con valores por defecto si no existe).
     */
    public static function actual(): self
    {
        return static::query()->firstOrCreate([], [
            'nombre_colegio' => 'CTP Los Chiles',
            'hora_entrada' => '07:00:00',
            'minutos_tolerancia' => 10,
            'registrar_salida' => false,
            'dias_lectivos' => [1, 2, 3, 4, 5],
            'segundos_pantalla' => 4,
        ]);
    }

    /**
     * Hora a partir de la cual una entrada se considera tardía (hora de entrada + tolerancia).
     */
    public function horaLimite(): string
    {
        return date('H:i:s', strtotime($this->hora_entrada) + $this->minutos_tolerancia * 60);
    }

    public function esTardia(CarbonInterface $momento): bool
    {
        return $momento->format('H:i:s') > $this->horaLimite();
    }
}
