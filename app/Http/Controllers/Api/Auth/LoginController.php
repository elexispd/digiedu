<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Mail\TwoFactorOtpMail;

class LoginController extends Controller
{
    /**
     * Authenticate user and return a token (or prompt for 2FA if enabled).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid login credentials',
            ], 401);
        }

        // When 2FA is enabled, issue a challenge token and email a 6-digit OTP
        if ($user->two_factor_enabled) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $challengeToken = Str::random(64);
            $targetEmail = $user->two_factor_email ?: $user->email;

            // Cache 2FA login challenge for 10 minutes
            Cache::put('2fa_login_' . $challengeToken, [
                'user_id' => $user->id,
                'otp'     => $otp,
            ], now()->addMinutes(10));

            try {
                Mail::to($targetEmail)->send(new TwoFactorOtpMail($otp, 'login'));
            } catch (\Exception $e) {
                Log::error('Failed to send 2FA login email: ' . $e->getMessage());
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Two-factor authentication required. A 6-digit verification code has been sent to your email.',
                'data'    => [
                    'two_factor_required' => true,
                    'challenge_token'     => $challengeToken,
                    'masked_email'        => $this->maskEmail($targetEmail),
                ],
            ]);
        }

        // Create Sanctum Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ]
        ]);
    }

    /**
     * Mask email address for security.
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', max(1, $len - 2)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }
}
