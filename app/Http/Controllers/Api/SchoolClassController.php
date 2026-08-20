<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Http\Requests\StoreSchoolClassRequest;
use App\Http\Requests\UpdateSchoolClassRequest;
use App\Enums\ClassCategoryEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolClassController extends Controller
{
    /**
     * Get all available class categories.
     */
    public function getCategories(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => ClassCategoryEnum::values()
        ]);
    }

    /**
     * Display a listing of the classes, optionally filtered by search query.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->query('search') ?? $request->query('query');

        $classesQuery = SchoolClass::with('arms', 'leadTeacher');

        if ($query) {
            $classesQuery->where('name', 'LIKE', "%{$query}%")
                ->orWhere('category', 'LIKE', "%{$query}%")
                ->orWhereHas('leadTeacher', function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                });
        }

        $classes = $classesQuery->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $classes
        ]);
    }

    /**
     * Store a newly created class in storage.
     */
    public function store(StoreSchoolClassRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $schoolClass = SchoolClass::create([
                'name' => $request->name,
                'category' => $request->category,
                'lead_teacher_id' => $request->lead_teacher_id
            ]);

            if ($request->has('arms') && is_array($request->arms)) {
                foreach ($request->arms as $armName) {
                    $schoolClass->arms()->create(['name' => $armName]);
                }
            }
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Class created successfully',
                'data' => $schoolClass->load('arms', 'leadTeacher')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create class'
            ], 500);
        }
    }

    /**
     * Display the specified class.
     */
    public function show(string $id): JsonResponse
    {
        $schoolClass = SchoolClass::with('arms', 'leadTeacher')->find($id);

        if (!$schoolClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Class not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $schoolClass
        ]);
    }

    /**
     * Update the specified class in storage.
     */
    public function update(UpdateSchoolClassRequest $request, string $id): JsonResponse
    {
        $schoolClass = SchoolClass::find($id);

        if (!$schoolClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Class not found'
            ], 404);
        }

        $schoolClass->update($request->only(['name', 'category', 'lead_teacher_id']));

        return response()->json([
            'status' => 'success',
            'message' => 'Class updated successfully',
            'data' => $schoolClass->load('arms')
        ]);
    }

    /**
     * Remove the specified class from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $schoolClass = SchoolClass::find($id);

        if (!$schoolClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Class not found'
            ], 404);
        }

        $schoolClass->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Class deleted successfully'
        ]);
    }
}
