<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\StoreAdministratorRequest;
use App\Http\Requests\UpdateAdministratorRequest;
use App\Http\Requests\BulkAdministratorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdministratorController extends Controller
{
    /**
     * Get all administrators.
     */
    public function index(): JsonResponse
    {
        // Fetch all users who have any role (assuming initial administrators all have a role)
        $admins = User::has('roles')->with('roles')->get();

        return response()->json([
            'status' => 'success',
            'data' => $admins
        ]);
    }

    /**
     * Add a single administrator.
     */
    public function store(StoreAdministratorRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'id' => (string) Str::uuid(),
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('password123'),
                'status' => 'pending'
            ]);

            $role = Role::find($request->role_id);
            $user->assignRole($role);

            // Generate 7-digit activation code
            $code = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            $cacheKey = 'staff_activation_code_' . $user->id;
            \Illuminate\Support\Facades\Cache::put($cacheKey, $code, now()->addMinutes(15));

            if (!empty($request->email)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\ActivationOtpMail($code));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Mail failed: ' . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Administrator created successfully',
                'data' => [
                    'user' => $user->load('roles'),
                    'activation_code' => $code
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to create administrator'], 500);
        }
    }

    /**
     * Bulk add administrators.
     */
    public function bulkStore(BulkAdministratorRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $createdUsers = collect();

            foreach ($request->validated('administrators') as $adminData) {
                // Check if email already exists to avoid unique constraint violation in loop
                $user = User::where('email', $adminData['email'])->first();

                if (!$user) {
                    $user = User::create([
                        'id' => (string) Str::uuid(),
                        'name' => $adminData['name'],
                        'email' => $adminData['email'],
                        'password' => Hash::make('password123')
                    ]);
                } else {
                    // Update name if user exists
                    $user->update(['name' => $adminData['name']]);
                }

                $role = Role::find($adminData['role_id']);
                // Sync roles to ensure they only have the selected designation
                $user->syncRoles([$role]);
                
                $createdUsers->push($user->load('roles'));
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Administrators saved successfully',
                'data' => $createdUsers
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save administrators: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an administrator.
     */
    public function update(UpdateAdministratorRequest $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Administrator not found'], 404);
        }

        DB::beginTransaction();
        try {
            $user->update($request->only(['name', 'email']));

            if ($request->has('role_id')) {
                $role = Role::find($request->role_id);
                $user->syncRoles([$role]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Administrator updated successfully',
                'data' => $user->load('roles')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update administrator'], 500);
        }
    }

    /**
     * Delete an administrator.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Administrator not found'], 404);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Administrator deleted successfully'
        ]);
    }
}
