<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\TwoFactorOtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TwoFactorController extends Controller
{
    /**
     * Send a 6-digit OTP to the email provided by the authenticated user to turn ON 2FA.
     */
    public function sendEnableOtp(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = '2fa_enable_otp_' . $user->id;

        // Store target email and OTP in cache for 10 minutes
        Cache::put($cacheKey, [
            'email' => $request->email,
            'otp'   => $otp,
        ], now()->addMinutes(10));

        try {
            Mail::to($request->email)->send(new TwoFactorOtpMail($otp, 'enable'));
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA setup email: ' . $e->getMessage());
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'A 6-digit verification code has been sent to ' . $request->email,
            'data'    => [
                'email' => $request->email,
            ],
        ]);
    }

    /**
     * Verify the 6-digit OTP and turn ON 2FA for the authenticated user.
     */
    public function verifyEnable(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $cacheKey = '2fa_enable_otp_' . $user->id;
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData || $cachedData['otp'] !== $request->otp) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or expired verification code.',
            ], 400);
        }

        $user->update([
            'two_factor_enabled'      => true,
            'two_factor_email'        => $cachedData['email'],
            'two_factor_confirmed_at' => now(),
        ]);

        Cache::forget($cacheKey);

        return response()->json([
            'status'  => 'success',
            'message' => 'Two-factor authentication has been enabled successfully.',
            'data'    => [
                'two_factor_enabled' => true,
                'two_factor_email'   => $user->two_factor_email,
                'user'               => new UserResource($user->fresh()),
            ],
        ]);
    }

    /**
     * Turn OFF 2FA for the authenticated user.
     */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->has('current_password')) {
            $request->validate(['current_password' => 'required|current_password']);
        }

        $user->update([
            'two_factor_enabled'      => false,
            'two_factor_email'        => null,
            'two_factor_confirmed_at' => null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Two-factor authentication has been disabled successfully.',
            'data'    => [
                'two_factor_enabled' => false,
                'user'               => new UserResource($user->fresh()),
            ],
        ]);
    }

    /**
     * Get the authenticated user's current 2FA status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'two_factor_enabled'      => (bool) $user->two_factor_enabled,
                'two_factor_email'        => $user->two_factor_email,
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Complete 2FA login by verifying the 6-digit OTP.
     */
    public function verifyLogin(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => 'required|string',
            'otp'             => 'required|string|size:6',
        ]);

        $cacheKey = '2fa_login_' . $request->challenge_token;
        $cached = Cache::get($cacheKey);

        if (!$cached || $cached['otp'] !== $request->otp) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or expired 2FA verification code.',
            ], 400);
        }

        $user = User::find($cached['user_id']);
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        Cache::forget($cacheKey);

        // Issue Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged in successfully',
            'data'    => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Resend the 6-digit OTP for an active 2FA login challenge.
     */
    public function resendLoginOtp(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => 'required|string',
        ]);

        $cacheKey = '2fa_login_' . $request->challenge_token;
        $cached = Cache::get($cacheKey);

        if (!$cached) {
            return response()->json([
                'status'  => 'error',
                'message' => '2FA login session has expired. Please log in again.',
            ], 400);
        }

        $user = User::find($cached['user_id']);
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $targetEmail = $user->two_factor_email ?: $user->email;

        // Reset the 10-minute cache window with the new OTP
        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'otp'     => $otp,
        ], now()->addMinutes(10));

        try {
            Mail::to($targetEmail)->send(new TwoFactorOtpMail($otp, 'login'));
        } catch (\Exception $e) {
            Log::error('Failed to resend 2FA login email: ' . $e->getMessage());
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'A new 2FA verification code has been sent to your email.',
        ]);
    }
}
