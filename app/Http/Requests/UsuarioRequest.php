<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    /**
     * El acceso ya lo limita la ruta al rol administrador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * La contraseña es obligatoria al crear y opcional al editar (vacía = no cambiarla).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:100'],
            'usuario' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'usuario')->ignore($usuario)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario)],
            'rol' => ['required', Rule::enum(Rol::class)],
            'activo' => ['boolean'],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'usuario' => strtolower(trim((string) $this->input('usuario'))),
            'activo' => $this->boolean('activo'),
        ]);
    }
}
