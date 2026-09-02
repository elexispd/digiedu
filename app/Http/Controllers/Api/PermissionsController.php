<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionsController extends Controller
{
    /**
     * Fetch all available permissions.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }
}
