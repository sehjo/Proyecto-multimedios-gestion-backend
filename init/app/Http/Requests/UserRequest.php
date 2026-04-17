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
            'name'         => ['required', 'string', 'max:255'],
            'lastname'     => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'     => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'min:8', 'max:255'],
            'user_type_id' => ['required', 'integer', 'exists:users_types,id'],
        ];
    }
}
