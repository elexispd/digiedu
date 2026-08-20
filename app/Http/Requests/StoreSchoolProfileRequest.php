<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolProfileRequest extends FormRequest
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
            'school_name' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'official_email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'physical_address' => 'required|string',
            'logo_url' => 'nullable|string|url|max:255',
        ];
    }
}
