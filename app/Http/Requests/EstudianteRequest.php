<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstudianteRequest extends FormRequest
{
    /**
     * El acceso ya lo limita la ruta al rol administrador.
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
            'cedula' => ['required', 'string', 'max:30', Rule::unique('estudiantes', 'cedula')->ignore($this->route('estudiante'))],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido1' => ['required', 'string', 'max:100'],
            'apellido2' => ['nullable', 'string', 'max:100'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'subgrupo_id' => ['required', 'integer', Rule::exists('subgrupos', 'id')],
            'activo' => ['boolean'],
            'anio_matricula' => ['sometimes', 'required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'encargado_nombre' => ['nullable', 'string', 'max:150'],
            'encargado_parentesco' => ['nullable', 'string', 'max:50'],
            'encargado_telefono' => ['nullable', 'string', 'max:30', 'regex:/^\+?[\d\s\-()]{8,20}$/'],
            'encargado_correo' => ['nullable', 'email', 'max:150'],
            'avisar_encargado' => ['boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cedula' => trim((string) $this->input('cedula')),
            'activo' => $this->boolean('activo'),
            'avisar_encargado' => $this->boolean('avisar_encargado'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'encargado_telefono.regex' => 'El teléfono del encargado debe tener al menos 8 dígitos (ej. 8888-7777 o +506 8888-7777).',
        ];
    }
}
