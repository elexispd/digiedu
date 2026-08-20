<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FetchRolesRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use App\Models\Role;

class RolesController extends Controller
{
    /**
     * Fetch available user registration roles.
     * 
     * @param FetchRolesRequest $request
     * @return JsonResponse
     */
    public function index(FetchRolesRequest $request): JsonResponse
    {
        // Return id and name so frontend can use the UUID in forms
        $roles = Role::where('name', '!=', 'admin')
            ->select('id', 'name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    /**
     * Get a single role by ID.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $role
        ]);
    }

    /**
     * Create a new role.
     * 
     * @param StoreRoleRequest $request
     * @return JsonResponse
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
        ], 201);
    }

    /**
     * Update an existing role.
     * 
     * @param UpdateRoleRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role not found'
            ], 404);
        }

        if ($role->name === 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'The admin role cannot be modified'
            ], 403);
        }

        if ($request->has('name')) {
            $role->name = $request->name;
            $role->save();
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully',
            'data' => $role->load('permissions')
        ]);
    }

    /**
     * Delete a role.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role not found'
            ], 404);
        }

        if ($role->name === 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'The admin role cannot be deleted'
            ], 403);
        }

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully'
        ]);
    }
}
