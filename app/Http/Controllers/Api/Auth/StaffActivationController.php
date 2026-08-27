<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StaffActivationController extends Controller
{
    /**
     * Activate a staff member account (Admin, Teacher, etc.)
     * Expects: email, code, password, password_confirmation
     */
    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:7',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()]
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        if ($user->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is already activated.'
            ], 400);
        }

        $cacheKey = 'staff_activation_code_' . $user->id;
        $cachedCode = Cache::get($cacheKey);

        if (!$cachedCode || $cachedCode !== $validated['code']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired activation code.'
            ], 400);
        }

        // Update password and set status to active
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'status' => 'active'
        ])->save();

        // Clear the cache
        Cache::forget($cacheKey);

        return response()->json([
            'status' => 'success',
            'message' => 'Staff account activated successfully. Password has been set.'
        ]);
    }
}
