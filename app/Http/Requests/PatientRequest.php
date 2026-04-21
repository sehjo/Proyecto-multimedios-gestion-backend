<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'lastname'    => ['required', 'string', 'max:255'],
            'nick'        => ['required', 'string', 'max:255'],
            'suffering'   => ['nullable', 'string', 'max:500'],
            'register_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
