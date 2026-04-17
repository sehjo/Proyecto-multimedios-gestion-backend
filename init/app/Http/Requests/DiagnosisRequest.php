<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosisRequest extends FormRequest
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
            'name'         => ['required', 'string', 'max:255'],
            'disease_id'   => ['nullable', 'integer', 'exists:disease,id'],
            'patient_id'   => ['required', 'integer', 'exists:patient,id'],
            'diagnoses_by' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
