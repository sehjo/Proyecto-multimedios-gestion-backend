<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class HasPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // @example patients.read
            'permission' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permission.required' => 'El parámetro permission es obligatorio.',
            'permission.string'   => 'El parámetro permission debe ser una cadena de texto.',
            'permission.max'      => 'El parámetro permission no puede superar los 255 caracteres.',
        ];
    }
}
