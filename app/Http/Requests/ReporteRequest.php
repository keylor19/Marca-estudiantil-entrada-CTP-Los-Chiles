<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Filtros comunes de los informes. El período es libre (desde / hasta): un día, una semana,
 * un mes, tres meses, todo el curso lectivo o cualquier rango personalizado.
 */
class ReporteRequest extends FormRequest
{
    /**
     * El acceso ya lo limita la ruta a administración y dirección.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'fecha' => ['nullable', 'date'],
            'seccion_id' => ['nullable', 'integer'],
            'subgrupo_id' => ['nullable', 'integer'],
            'cedula' => ['nullable', 'string', 'max:50'],
            'formato' => ['nullable', 'in:excel'],
        ];
    }

    public function desde(): Carbon
    {
        return $this->date('desde') ?? today()->startOfMonth();
    }

    public function hasta(): Carbon
    {
        return $this->date('hasta') ?? today();
    }

    public function fecha(): Carbon
    {
        return $this->date('fecha') ?? today();
    }

    public function quiereExcel(): bool
    {
        return $this->input('formato') === 'excel';
    }
}
