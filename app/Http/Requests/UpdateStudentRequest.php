<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
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
        $studentId = $this->route('id') ?? $this->route('student');

        return [
            // Basic Info
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string|in:Male,Female,Other',
            'dob' => 'required|date',
            'admission_number' => 'required|string|unique:students,admission_number,' . $studentId,
            'photo' => 'nullable|image|max:2048',
            'blood_group' => 'nullable|string|max:10',
            'genotype' => 'nullable|string|max:10',
            'nationality' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'current_status' => 'nullable|string|in:draft,active,graduated,suspended',

            // Academic Info
            'academic_session_id' => 'nullable|uuid|exists:academic_sessions,id',
            'term' => 'nullable|string',
            'admission_date' => 'nullable|date',
            'class_category' => 'nullable|string',
            'school_class_id' => 'nullable|uuid|exists:school_classes,id',
            'class_arm_id' => 'nullable|uuid|exists:class_arms,id',
            'student_type' => 'nullable|string|in:day,boarding',
            'house' => 'nullable|string|max:255',
            'roll_number' => 'nullable|string|max:255',
        ];
    }
}
