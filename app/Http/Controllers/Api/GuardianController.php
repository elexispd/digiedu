<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Imports\GuardiansImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreGuardianRequest;
use App\Http\Requests\UpdateGuardianRequest;
use App\Http\Requests\AssignStudentsToGuardianRequest;
use App\Http\Requests\ResetGuardianPasswordRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GuardianController extends Controller
{
    /**
     * Display a listing of the guardians.
     */
    public function index(): JsonResponse
    {
        $guardians = Guardian::with('user', 'students')->paginate(15);
        return response()->json([
            'status' => 'success',
            'data' => $guardians
        ]);
    }

    /**
     * Store a newly created guardian in storage.
     */
    public function store(StoreGuardianRequest $request): JsonResponse
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $data = $request->validated();
            
            // Create a User account for the guardian
            $user = \App\Models\User::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone_number'] ?? null,
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'status' => 'pending'
            ]);

            // Assign the 'parent' role
            $role = \App\Models\Role::where('name', 'parent')->first();
            if ($role) {
                $user->assignRole($role);
            }

            // Assign user_id to guardian data
            $data['user_id'] = $user->id;
            $data['status'] = 'pending';
            
            $guardian = Guardian::create($data);

            // Generate 7-digit activation code
            $code = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            $cacheKey = 'guardian_activation_code_' . $user->id;
            \Illuminate\Support\Facades\Cache::put($cacheKey, $code, now()->addMinutes(15));

            if (!empty($data['email'])) {
                // Try sending Email, ignore if it fails
                try {
                    \Illuminate\Support\Facades\Mail::to($data['email'])->send(new \App\Mail\ActivationOtpMail($code));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Mail failed: ' . $e->getMessage());
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Guardian created successfully',
                'data' => [
                    'guardian' => $guardian,
                    'activation_code' => $code
                ]
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create guardian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified guardian.
     */
    public function show(Guardian $guardian): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $guardian->load('user', 'students')
        ]);
    }

    /**
     * Update the specified guardian in storage.
     */
    public function update(UpdateGuardianRequest $request, Guardian $guardian): JsonResponse
    {
        $guardian->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Guardian updated successfully',
            'data' => $guardian
        ]);
    }

    /**
     * Remove the specified guardian from storage.
     */
    public function destroy(Guardian $guardian): JsonResponse
    {
        $guardian->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Guardian deleted successfully'
        ]);
    }

    /**
     * Search guardians by name or phone.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('query');

        if (!$query) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $guardians = Guardian::where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('phone_number', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $guardians
        ]);
    }

    /**
     * Import guardians from Excel/CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xls,xlsx'
        ]);

        try {
            Excel::import(new GuardiansImport, $request->file('file'));

            return response()->json([
                'status' => 'success',
                'message' => 'Guardians imported successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import guardians: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign students to a guardian.
     */
    public function assignStudents(AssignStudentsToGuardianRequest $request, Guardian $guardian): JsonResponse
    {
        $syncData = [];
        foreach ($request->validated('students') as $studentData) {
            $syncData[$studentData['student_id']] = ['relationship' => $studentData['relationship']];
        }

        $guardian->students()->syncWithoutDetaching($syncData);

        return response()->json([
            'status' => 'success',
            'message' => 'Students assigned to guardian successfully',
            'data' => $guardian->load('students')
        ]);
    }

    /**
     * Send a 6-digit pin to the guardian's phone number for password reset.
     */
    public function sendResetPin(Request $request, Guardian $guardian, \App\Services\SmsService $smsService): JsonResponse
    {
        if (!$guardian->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'This guardian does not have an active user account.'
            ], 400);
        }

        // Generate 6-digit pin
        $pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store pin in cache for 15 minutes
        $cacheKey = 'guardian_reset_pin_' . $guardian->id;
        Cache::put($cacheKey, $pin, now()->addMinutes(15));

        // Use the SMS service to send the pin
        $message = "Your password reset pin is {$pin}. It will expire in 15 minutes.";
        $smsService->send($guardian->phone_number, $message);

        return response()->json([
            'status' => 'success',
            'message' => 'A 6-digit reset pin has been generated.',
            'data' => [
                'pin' => $pin // Returning pin as requested since there's no live SMS gateway yet
            ]
        ]);
    }

    /**
     * Verify the 6-digit pin and reset the guardian's password.
     */
    public function resetPassword(ResetGuardianPasswordRequest $request, Guardian $guardian): JsonResponse
    {
        if (!$guardian->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'This guardian does not have an active user account.'
            ], 400);
        }

        $cacheKey = 'guardian_reset_pin_' . $guardian->id;
        $cachedPin = Cache::get($cacheKey);

        if (!$cachedPin || $cachedPin !== $request->pin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired pin.'
            ], 400);
        }

        // Reset password
        $user = $guardian->user;
        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        // Clear pin from cache
        Cache::forget($cacheKey);

        return response()->json([
            'status' => 'success',
            'message' => 'Password has been reset successfully.'
        ]);
    }
}
