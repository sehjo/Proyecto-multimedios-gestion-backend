<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * All filters are optional. Validating role/status here avoids 500s on
     * unknown values and keeps the query safe.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // @example Laura
            'name'     => ['sometimes', 'string', 'max:255'],
            // @example Medico
            'role'     => ['sometimes', 'string', 'exists:roles,name'],
            // @example ACTIVE
            'status'   => ['sometimes', 'string', Rule::enum(UserStatus::class)],
            // @example 15
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page'     => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.exists'      => 'El rol seleccionado no existe.',
            'status.enum'      => 'El estado debe ser "ACTIVE" o "INACTIVE".',
            'per_page.integer' => 'El tamaño de página debe ser un número.',
            'per_page.min'     => 'El tamaño de página debe ser al menos 1.',
            'per_page.max'     => 'El tamaño de página no puede superar 100.',
        ];
    }
}
