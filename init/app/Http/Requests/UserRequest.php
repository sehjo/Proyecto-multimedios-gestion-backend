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

        $rules = [
            // @example Laura
            'name'     => ['required', 'string', 'max:255'],
            // @example Soto
            'lastname' => ['required', 'string', 'max:255'],
            // @example laura.soto@ccss.cr
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // @example Doctor1234!
            'password' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'min:8', 'max:255'],
        ];

        // The role is only set on creation. On update, roles are managed
        // exclusively via PUT /users/{user}/role, so this request ignores it.
        if ($this->isMethod('POST')) {
            // @example Medico
            $rules['role'] = ['required', 'string', 'exists:roles,name'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'role.required' => 'El rol es obligatorio.',
            'role.exists'   => 'El rol seleccionado no existe.',
        ];
    }
}
