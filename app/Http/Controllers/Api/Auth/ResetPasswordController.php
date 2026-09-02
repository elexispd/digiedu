<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    /**
     * Reset the user's password using an OTP.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $cacheKey = 'password_reset_token_' . $request->email;
        $cachedToken = Cache::get($cacheKey);

        if (!$cachedToken || $cachedToken !== $request->token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired token.'
            ], 400);
        }

        // Reset password
        $user = User::where('email', $request->email)->first();
        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        // Clear token
        Cache::forget($cacheKey);

        return response()->json([
            'status' => 'success',
            'message' => 'Your password has been reset successfully.'
        ]);
    }
}
