<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassArm;
use App\Models\SchoolClass;
use App\Http\Requests\StoreClassArmRequest;
use App\Http\Requests\UpdateClassArmRequest;
use Illuminate\Http\JsonResponse;

class ClassArmController extends Controller
{
    /**
     * Store a newly created class arm in storage.
     */
    public function store(StoreClassArmRequest $request, string $classId): JsonResponse
    {
        $schoolClass = SchoolClass::find($classId);

        if (!$schoolClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Class not found'
            ], 404);
        }

        $arm = $schoolClass->arms()->create([
            'name' => $request->name,
            'class_capacity' => $request->class_capacity
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Class arm created successfully',
            'data' => $arm
        ], 201);
    }

    /**
     * Update the specified class arm in storage.
     */
    public function update(UpdateClassArmRequest $request, string $id): JsonResponse
    {
        $arm = ClassArm::find($id);

        if (!$arm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Class arm not found'
            ], 404);
        }

        $arm->update($request->only(['name', 'class_capacity']));

        return response()->json([
            'status' => 'success',
            'message' => 'Class arm updated successfully',
            'data' => $arm
        ]);
    }

    /**
     * Remove the specified class arm from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $arm = ClassArm::find($id);

        if (!$arm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Class arm not found'
            ], 404);
        }

        $arm->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Class arm deleted successfully'
        ]);
    }
}
