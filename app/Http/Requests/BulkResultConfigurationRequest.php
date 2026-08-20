<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkResultConfigurationRequest extends FormRequest
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
            'configurations' => 'required|array',
            'configurations.*.assessment_type' => 'required|string|max:255',
            'configurations.*.percentage' => 'required|integer|min:1|max:100',
            'configurations.*.is_exam' => 'boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $total = collect($this->input('configurations'))->sum('percentage');
            if ($total !== 100) {
                $validator->errors()->add('configurations', "The cumulative total of all assessment percentages must equal exactly 100%. Current total: {$total}%.");
            }
        });
    }
}
