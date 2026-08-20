<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkGradeRuleRequest extends FormRequest
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
            'class_category' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\ClassCategoryEnum::class)],
            'rules' => 'required|array',
            'rules.*.grade' => 'required|string|max:10',
            'rules.*.min_score' => 'required|integer|min:0|max:100',
            'rules.*.max_score' => 'required|integer|min:0|max:100|gte:rules.*.min_score',
            'rules.*.remark' => 'required|string|max:255',
        ];
    }
}
