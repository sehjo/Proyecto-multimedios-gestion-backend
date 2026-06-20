<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
            // @example Recepcion
            'name'          => ['required', 'string', 'max:50', 'not_regex:/^\s*$/', 'unique:roles,name'],
            'permissions'   => ['sometimes', 'array'],
            // @example patients.create
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre del rol es obligatorio.',
            'name.max'           => 'El nombre del rol no puede superar los 50 caracteres.',
            'name.not_regex'     => 'El nombre del rol no puede estar vacío.',
            'name.unique'        => 'Ya existe un rol con ese nombre.',
            'permissions.array'  => 'Los permisos deben enviarse como una lista.',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no existen.',
        ];
    }
}
