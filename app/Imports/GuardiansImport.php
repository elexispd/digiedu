<?php

namespace App\Imports;

use App\Models\Guardian;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class GuardiansImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Throw an exception if the file doesn't have the required headers
        if (!array_key_exists('first_name', $row) || !array_key_exists('last_name', $row) || !array_key_exists('phone_number', $row)) {
            throw new \Exception("Invalid Excel Template. Please ensure you are uploading the Guardian template with 'first_name', 'last_name', and 'phone_number' columns.");
        }

        // Skip genuinely empty rows
        if (empty($row['first_name']) && empty($row['last_name']) && empty($row['phone_number'])) {
            return null;
        }

        // Check if guardian already exists by phone or email
        $existing = Guardian::where('phone_number', $row['phone_number'])
            ->orWhere(function ($query) use ($row) {
                if (!empty($row['email'])) {
                    $query->where('email', $row['email']);
                }
            })->first();

        if ($existing) {
            return null; // Skip existing
        }

        return new Guardian([
            'id' => (string) Str::uuid(),
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'phone_number' => $row['phone_number'],
            'occupation' => $row['occupation'] ?? null,
            'email' => $row['email'] ?? null,
            'home_address' => $row['home_address'] ?? null,
            'lga' => $row['lga'] ?? null,
            'state' => $row['state'] ?? null,
        ]);
    }
}
