<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExtracurricularActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'coordinator_id' => 'nullable|uuid|exists:users,id',
            'schedule_days' => 'sometimes|array',
            'schedule_days.*' => 'string|max:20',
            'venue' => 'sometimes|string|max:255',
        ];
    }
}
