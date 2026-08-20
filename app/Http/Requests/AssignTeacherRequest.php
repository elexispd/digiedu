<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTeacherRequest extends FormRequest
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
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'uuid', 'exists:users,id'],
            'school_class_id' => ['required', 'uuid', 'exists:school_classes,id'],
            'academic_session_id' => ['required', 'uuid', 'exists:academic_sessions,id'],
        ];
    }
}
