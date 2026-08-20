<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20|unique:guardians,phone_number',
            'occupation' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:guardians,email',
            'home_address' => 'nullable|string',
            'lga' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
        ];
    }
}
