<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportStudentsRequest extends FormRequest
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
            'file' => 'required|file|mimes:csv,txt,xls,xlsx',
            'academic_session_id' => 'required|uuid|exists:academic_sessions,id',
            'term' => 'required|string',
            'class_category' => 'required|string',
            'school_class_id' => 'required|uuid|exists:school_classes,id',
            'class_arm_id' => 'required|uuid|exists:class_arms,id',
        ];
    }
}
