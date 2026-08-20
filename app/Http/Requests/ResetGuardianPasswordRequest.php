<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetGuardianPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
