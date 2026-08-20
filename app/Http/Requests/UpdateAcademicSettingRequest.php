<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicSettingRequest extends FormRequest
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
            'academic_session_id' => 'sometimes|nullable|uuid|exists:academic_sessions,id',
            'terms_per_year' => 'sometimes|integer|min:1|max:4',
            'pass_threshold' => 'sometimes|integer|min:0|max:100',
            'grading_standard' => 'sometimes|string|max:255'
        ];
    }
}
