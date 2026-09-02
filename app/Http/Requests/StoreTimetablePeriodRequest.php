<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimetablePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'period_number' => 'nullable|integer|min:1|max:20',
            'start_time' => 'required|string|max:20',
            'end_time' => 'required|string|max:20',
            'is_break' => 'sometimes|boolean',
            'order' => 'sometimes|integer|min:1',
        ];
    }
}
