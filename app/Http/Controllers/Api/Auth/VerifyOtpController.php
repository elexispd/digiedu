<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VerifyOtpController extends Controller
{
    /**
     * Verify the OTP sent to the user's email.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $otpCacheKey = 'password_reset_otp_' . $request->email;
        $cachedOtp = Cache::get($otpCacheKey);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP.'
            ], 400);
        }

        // Generate a token for the actual reset request
        $token = Str::random(60);
        $tokenCacheKey = 'password_reset_token_' . $request->email;
        
        // Cache the token for 15 minutes
        Cache::put($tokenCacheKey, $token, now()->addMinutes(15));
        
        // Clear the OTP
        Cache::forget($otpCacheKey);

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified successfully.',
            'token' => $token
        ]);
    }
}
