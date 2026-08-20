<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentGuardiansRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'guardians' => 'required|array|min:1',
            'guardians.*.guardian_id' => 'nullable|uuid|exists:guardians,id',
            'guardians.*.first_name' => 'required_without:guardians.*.guardian_id|string|max:255',
            'guardians.*.last_name' => 'required_without:guardians.*.guardian_id|string|max:255',
            'guardians.*.relationship' => 'required|string|max:255',
            'guardians.*.phone_number' => 'required_without:guardians.*.guardian_id|string|max:20',
            'guardians.*.occupation' => 'nullable|string|max:255',
            'guardians.*.email' => 'nullable|email|max:255',
            'guardians.*.home_address' => 'nullable|string',
            'guardians.*.lga' => 'nullable|string|max:255',
            'guardians.*.state' => 'nullable|string|max:255',
        ];
    }
}
