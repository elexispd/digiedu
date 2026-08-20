<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConductLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'sometimes|uuid|exists:students,id',
            'teacher_id' => 'sometimes|uuid|exists:users,id',
            'type' => ['sometimes', Rule::in(['merit', 'discipline'])],
            'category' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'details' => 'sometimes|string',
        ];
    }
}
