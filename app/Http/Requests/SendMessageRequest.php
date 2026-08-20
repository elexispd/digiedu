<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in the controller via role checks
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'uuid', 'exists:users,id'],
            'body'         => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'recipient_id.required' => 'A recipient must be specified.',
            'recipient_id.exists'   => 'The selected recipient does not exist.',
            'body.required'         => 'Message body cannot be empty.',
            'body.max'              => 'Message must not exceed 5000 characters.',
        ];
    }
}
