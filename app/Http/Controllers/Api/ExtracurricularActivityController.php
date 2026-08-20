<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtracurricularActivity;
use App\Http\Requests\StoreExtracurricularActivityRequest;
use App\Http\Requests\UpdateExtracurricularActivityRequest;
use App\Http\Requests\EnrollStudentRequest;
use Illuminate\Http\JsonResponse;

class ExtracurricularActivityController extends Controller
{
    /**
     * Display a listing of activities.
     */
    public function index(): JsonResponse
    {
        $activities = ExtracurricularActivity::with('coordinator:id,name')->withCount('students')->get();

        return response()->json([
            'status' => 'success',
            'data' => $activities
        ]);
    }

    /**
     * Store a newly created activity.
     */
    public function store(StoreExtracurricularActivityRequest $request): JsonResponse
    {
        $activity = ExtracurricularActivity::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Activity created successfully',
            'data' => $activity->load('coordinator:id,name')->loadCount('students')
        ], 201);
    }

    /**
     * Display the specified activity.
     */
    public function show(ExtracurricularActivity $activity): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $activity->load('coordinator:id,name')->loadCount('students')
        ]);
    }

    /**
     * Update the specified activity.
     */
    public function update(UpdateExtracurricularActivityRequest $request, ExtracurricularActivity $activity): JsonResponse
    {
        $activity->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Activity updated successfully',
            'data' => $activity->load('coordinator:id,name')->loadCount('students')
        ]);
    }

    /**
     * Remove the specified activity.
     */
    public function destroy(ExtracurricularActivity $activity): JsonResponse
    {
        $activity->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Activity deleted successfully'
        ]);
    }

    /**
     * Enroll multiple students into the activity.
     */
    public function enrollStudents(EnrollStudentRequest $request, ExtracurricularActivity $activity): JsonResponse
    {
        // syncWithoutDetaching prevents duplicates while keeping existing enrollments
        $activity->students()->syncWithoutDetaching($request->student_ids);

        return response()->json([
            'status' => 'success',
            'message' => 'Students enrolled successfully'
        ]);
    }

    /**
     * Unenroll multiple students from the activity.
     */
    public function unenrollStudents(EnrollStudentRequest $request, ExtracurricularActivity $activity): JsonResponse
    {
        $activity->students()->detach($request->student_ids);

        return response()->json([
            'status' => 'success',
            'message' => 'Students unenrolled successfully'
        ]);
    }

    /**
     * Get the roster of enrolled students.
     */
    public function roster(ExtracurricularActivity $activity): JsonResponse
    {
        $students = $activity->students()->orderBy('first_name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $students
        ]);
    }
}
