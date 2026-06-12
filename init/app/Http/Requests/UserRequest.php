<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            // @example Laura
            'name'     => ['required', 'string', 'max:255'],
            // @example Soto
            'lastname' => ['required', 'string', 'max:255'],
            // @example laura.soto@ccss.cr
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // @example Doctor1234!
            'password' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'min:8', 'max:255'],
            // The role is validated against Spatie's roles (roles table). Never direct permissions.
            // @example Medico
            'role'     => ['required', 'string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'El rol es obligatorio.',
            'role.exists'   => 'El rol seleccionado no existe.',
        ];
    }
}
