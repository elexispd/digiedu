<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $guardianId = $this->route('guardian');

        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20|unique:guardians,phone_number,' . $guardianId,
            'occupation' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:guardians,email,' . $guardianId,
            'home_address' => 'nullable|string',
            'lga' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
        ];
    }
}
