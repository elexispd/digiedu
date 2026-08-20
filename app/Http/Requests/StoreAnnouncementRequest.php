<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'urgency_level' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'audience_target' => 'required|array',
            'audience_target.*' => 'string|max:50',
            'scheduled_date' => 'nullable|date',
            'message_content' => 'required|string',
        ];
    }
}
