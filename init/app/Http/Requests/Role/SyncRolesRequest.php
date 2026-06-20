<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolesRequest extends FormRequest
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
            // The complete final set of roles for the user.
            'roles'   => ['required', 'array', 'min:1'],
            // @example Administrador
            'roles.*' => ['string', 'distinct', 'exists:roles,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required'  => 'Debes indicar al menos un rol.',
            'roles.array'     => 'Los roles deben enviarse como una lista.',
            'roles.min'       => 'El usuario debe conservar al menos un rol.',
            'roles.*.exists'  => 'Uno o más roles seleccionados no existen.',
            'roles.*.distinct' => 'La lista de roles no puede tener duplicados.',
        ];
    }
}
