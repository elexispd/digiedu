<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignStudentsToGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'students' => 'required|array|min:1',
            'students.*.student_id' => 'required|uuid|exists:students,id',
            'students.*.relationship' => 'required|string|max:255',
        ];
    }
}
