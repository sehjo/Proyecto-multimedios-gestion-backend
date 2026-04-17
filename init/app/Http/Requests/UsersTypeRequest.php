<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsersTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'El nombre del tipo de usuario no puede exceder los 255 caracteres (Error: MAX_LENGTH_EXCEEDED).',
            'name.required' => 'El nombre del tipo de usuario es obligatorio.',
        ];
    }
}
