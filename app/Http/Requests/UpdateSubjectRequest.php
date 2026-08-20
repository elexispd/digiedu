<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject')?->id;

        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:subjects,code,' . $subjectId,
            'description' => 'nullable|string',
            'department' => 'nullable|string|max:255',
            'weekly_class_hours' => 'nullable|integer|min:0',
        ];
    }
}
