<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleUserController extends Controller
{
    /**
     * Display a listing of users with the given role.
     */
    public function index(Request $request, string $roleName): JsonResponse
    {
        Gate::authorize('manage-users');
        
        // Ensure role exists
        if (!Role::where('name', $roleName)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Role not found'], 404);
        }

        $perPage = $request->input('per_page', 15);
        $users = User::role($roleName)->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Display the specified user for the given role.
     */
    public function show(string $roleName, string $id): JsonResponse
    {
        Gate::authorize('manage-users');
        
        $user = User::role($roleName)->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    /**
     * Update the specified user's basic info.
     */
    public function update(Request $request, string $roleName, string $id): JsonResponse
    {
        Gate::authorize('manage-users');
        
        $user = User::role($roleName)->findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|nullable|string|unique:users,phone,' . $user->id,
            'status' => 'sometimes|required|string|in:active,pending,suspended'
        ]);
        
        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => ucfirst($roleName) . ' updated successfully',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(string $roleName, string $id): JsonResponse
    {
        Gate::authorize('manage-users');
        
        $user = User::role($roleName)->findOrFail($id);

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => ucfirst($roleName) . ' deleted successfully'
        ]);
    }
}
