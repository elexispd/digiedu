<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentAccountSetupRequest extends FormRequest
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
            'allow_student_portal' => 'required|boolean',
            'allow_parent_portal' => 'required|boolean',
            'student_password' => 'required_if:allow_student_portal,true|string|min:6',
            'parent_password' => 'required_if:allow_parent_portal,true|string|min:6',
        ];
    }
}
