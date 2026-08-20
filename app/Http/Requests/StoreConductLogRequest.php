<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConductLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|uuid|exists:students,id',
            'teacher_id' => 'required|uuid|exists:users,id',
            'type' => ['required', Rule::in(['merit', 'discipline'])],
            'category' => 'required|string|max:255',
            'date' => 'required|date',
            'details' => 'required|string',
        ];
    }
}
