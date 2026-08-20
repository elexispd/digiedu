<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordOtpMail;
use App\Models\User;

class ForgotPasswordController extends Controller
{
    /**
     * Send a 6-digit OTP to the given user for password reset.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // Generate a 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store it in Cache for 15 minutes
        Cache::put('password_reset_otp_' . $request->email, $otp, now()->addMinutes(15));

        // Send Email
        Mail::to($request->email)->send(new ResetPasswordOtpMail($otp));

        return response()->json([
            'status' => 'success',
            'message' => 'A password reset OTP has been sent to your email.'
        ]);
    }
}
