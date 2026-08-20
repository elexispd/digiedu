<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\StudentDocument;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\StoreStudentBasicInfoRequest;
use App\Http\Requests\UpdateStudentAcademicInfoRequest;
use App\Http\Requests\UpdateStudentGuardiansRequest;
use App\Http\Requests\StoreStudentDocumentRequest;
use App\Http\Requests\UpdateStudentAccountSetupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentRegistrationController extends Controller
{
    /**
     * Step 1: Basic Info (Creates draft student)
     */
    public function storeBasicInfo(StoreStudentBasicInfoRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('students/photos', 'public');
        }

        $student = Student::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Basic info saved as draft',
            'data' => $student
        ], 201);
    }

    /**
     * Step 2: Academic Info
     */
    public function updateAcademicInfo(UpdateStudentAcademicInfoRequest $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $student->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Academic info updated',
            'data' => $student
        ]);
    }

    /**
     * Step 3: Parent & Guardian
     */
    public function updateGuardians(UpdateStudentGuardiansRequest $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $syncData = [];

            foreach ($request->validated('guardians') as $gData) {
                if (isset($gData['guardian_id'])) {
                    $guardian = Guardian::findOrFail($gData['guardian_id']);
                } else {
                    $guardian = Guardian::create([
                        'first_name' => $gData['first_name'],
                        'last_name' => $gData['last_name'],
                        'phone_number' => $gData['phone_number'],
                        'occupation' => $gData['occupation'] ?? null,
                        'email' => $gData['email'] ?? null,
                        'home_address' => $gData['home_address'] ?? null,
                        'lga' => $gData['lga'] ?? null,
                        'state' => $gData['state'] ?? null,
                    ]);
                }
                
                $syncData[$guardian->id] = ['relationship' => $gData['relationship']];
            }

            $student->guardians()->sync($syncData);
            
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Guardians updated',
                'data' => $student->load('guardians')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save guardians'], 500);
        }
    }

    /**
     * Step 4: Documents
     */
    public function storeDocument(StoreStudentDocumentRequest $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $file = $request->file('file');
        
        $path = $file->store('students/documents', 'public');

        $document = StudentDocument::create([
            'student_id' => $student->id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Document uploaded',
            'data' => $document
        ], 201);
    }

    /**
     * Step 5: Account Setup & Finalize
     */
    public function updateAccountSetup(UpdateStudentAccountSetupRequest $request, string $id): JsonResponse
    {
        $student = Student::with('guardians')->findOrFail($id);
        
        DB::beginTransaction();
        try {
            $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
            $parentRole = Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);

            // Create Student User
            if ($request->allow_student_portal) {
                // Determine username/email. The instructions say "student username is also the student's reg number"
                // Assuming we use admission_number as email or a specific username field if added later.
                // We'll use a generated email if none exists, or admission_number@school.edu.
                $email = $student->admission_number . '@school.local'; 
                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'email' => $email,
                    'password' => Hash::make($request->student_password)
                ]);
                $user->assignRole($studentRole);
                $student->update(['user_id' => $user->id]);
            }

            // Create Parent Users
            if ($request->allow_parent_portal) {
                foreach ($student->guardians as $guardian) {
                    if (!$guardian->user_id) {
                        $email = $guardian->email ?? ($guardian->phone_number . '@guardian.local');
                        $pUser = User::create([
                            'id' => (string) Str::uuid(),
                            'name' => $guardian->first_name . ' ' . $guardian->last_name,
                            'email' => $email,
                            'phone' => $guardian->phone_number,
                            'password' => Hash::make($request->parent_password),
                            'status' => 'pending'
                        ]);
                        $pUser->assignRole($parentRole);
                        $guardian->update(['user_id' => $pUser->id]);
                    }
                }
            }

            $student->update(['current_status' => 'active']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Registration finalized and accounts created',
                'data' => $student->load('user', 'guardians.user')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to finalize setup: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import Students from Excel/CSV (Targeted Import)
     */
    public function import(\App\Http\Requests\ImportStudentsRequest $request): JsonResponse
    {
        try {
            // Pass the validated non-file fields to the Importer
            $importData = $request->except('file');
            
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StudentsImport($importData), $request->file('file'));

            return response()->json([
                'status' => 'success',
                'message' => 'Students imported successfully and accounts generated'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import students: ' . $e->getMessage()
            ], 500);
        }
    }
}
