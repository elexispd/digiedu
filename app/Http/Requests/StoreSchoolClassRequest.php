<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolClassRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'category' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\ClassCategoryEnum::class)],
            'lead_teacher_id' => 'nullable|uuid|exists:users,id',
            'arms' => 'nullable|array',
            'arms.*' => 'string|max:255'
        ];
    }
}
