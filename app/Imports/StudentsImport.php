<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class StudentsImport implements ToModel, WithHeadingRow
{
    protected $academic_session_id;
    protected $term;
    protected $class_category;
    protected $school_class_id;
    protected $class_arm_id;

    public function __construct($data)
    {
        $this->academic_session_id = $data['academic_session_id'];
        $this->term = $data['term'];
        $this->class_category = $data['class_category'];
        $this->school_class_id = $data['school_class_id'];
        $this->class_arm_id = $data['class_arm_id'];
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Throw an exception if the file doesn't have the required headers
        if (!array_key_exists('first_name', $row) || !array_key_exists('last_name', $row) || !array_key_exists('admission_number', $row)) {
            throw new \Exception("Invalid Excel Template. Please ensure you are uploading the Student template with 'first_name', 'last_name', and 'admission_number' columns.");
        }

        // Skip genuinely empty rows
        if (empty($row['first_name']) && empty($row['last_name']) && empty($row['admission_number'])) {
            return null;
        }

        // Check if student already exists by admission number
        if (Student::where('admission_number', $row['admission_number'])->exists()) {
            return null;
        }

        // Parse date of birth (Excel dates or strings)
        $dob = null;
        if (!empty($row['dob'])) {
            try {
                if (is_numeric($row['dob'])) {
                    $dob = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['dob'])->format('Y-m-d');
                } else {
                    $dob = Carbon::parse($row['dob'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Ignore parsing errors, keep null
            }
        }

        // Generate email for portal account
        $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $row['admission_number'])) . '@school.local';
        
        // Ensure email uniqueness fallback
        if (User::where('email', $email)->exists()) {
            $email = Str::random(5) . '_' . $email;
        }

        // Create Portal Account instantly
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $email,
            'password' => Hash::make('password123')
        ]);
        
        $role = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $user->assignRole($role);

        return new Student([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'],
            'gender' => $row['gender'] ?? 'Other',
            'dob' => $dob,
            'admission_number' => $row['admission_number'],
            'blood_group' => $row['blood_group'] ?? null,
            'genotype' => $row['genotype'] ?? null,
            'nationality' => $row['nationality'] ?? null,
            'religion' => $row['religion'] ?? null,
            
            // Attached from Targeted Import
            'academic_session_id' => $this->academic_session_id,
            'term' => $this->term,
            'admission_date' => now()->format('Y-m-d'),
            'class_category' => $this->class_category,
            'school_class_id' => $this->school_class_id,
            'class_arm_id' => $this->class_arm_id,
            'student_type' => strtolower($row['student_type'] ?? 'day'),
            
            // Active status
            'current_status' => 'active',
        ]);
    }
}
