<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtracurricularActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'coordinator_id' => 'nullable|uuid|exists:users,id',
            'schedule_days' => 'required|array',
            'schedule_days.*' => 'string|max:20',
            'venue' => 'required|string|max:255',
        ];
    }
}
