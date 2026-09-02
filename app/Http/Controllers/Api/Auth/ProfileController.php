<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Http\Resources\UserResource;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => new UserResource($request->user()),
            ]
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
            'biometric_enabled' => 'sometimes|boolean',
            'push_notifications_enabled' => 'sometimes|boolean',
            'email_notifications_enabled' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => new UserResource($user->fresh()),
            ]
        ]);
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get the authenticated user's notification settings.
     */
    public function getNotificationSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'push_notifications_enabled' => (bool) $user->push_notifications_enabled,
                'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
            ]
        ]);
    }

    /**
     * Update the authenticated user's notification settings (toggle push and email notifications).
     */
    public function updateNotificationSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'push_notifications_enabled' => 'sometimes|boolean',
            'email_notifications_enabled' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification settings updated successfully',
            'data' => [
                'push_notifications_enabled' => (bool) $user->push_notifications_enabled,
                'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
            ]
        ]);
    }
}
