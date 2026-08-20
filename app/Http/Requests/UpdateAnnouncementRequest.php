<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'urgency_level' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'audience_target' => 'sometimes|array',
            'audience_target.*' => 'string|max:50',
            'scheduled_date' => 'nullable|date',
            'message_content' => 'sometimes|string',
        ];
    }
}
