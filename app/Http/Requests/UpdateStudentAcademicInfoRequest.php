<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentAcademicInfoRequest extends FormRequest
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
            'academic_session_id' => 'required|uuid|exists:academic_sessions,id',
            'term' => 'required|string',
            'admission_date' => 'required|date',
            'class_category' => 'required|string',
            'school_class_id' => 'required|uuid|exists:school_classes,id',
            'class_arm_id' => 'required|uuid|exists:class_arms,id',
            'student_type' => 'required|string|in:day,boarding',
            'house' => 'nullable|string|max:255',
            'roll_number' => 'nullable|string|max:255',
        ];
    }
}
