<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Requests\AssignTeacherRequest;
use App\Models\SubjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Display a listing of the subjects.
     */
    public function index(): JsonResponse
    {
        $subjects = Subject::orderBy('name')->paginate(15);
        
        return response()->json([
            'status' => 'success',
            'data' => $subjects
        ]);
    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = Subject::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Subject created successfully',
            'data' => $subject
        ], 201);
    }

    /**
     * Display the specified subject.
     */
    public function show(Subject $subject): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $subject
        ]);
    }

    /**
     * Update the specified subject in storage.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Subject updated successfully',
            'data' => $subject
        ]);
    }

    /**
     * Remove the specified subject from storage.
     */
    public function destroy(Subject $subject): JsonResponse
    {
        $subject->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Subject deleted successfully'
        ]);
    }

    /**
     * Search subjects by name or code.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('query');

        if (!$query) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $subjects = Subject::where('name', 'LIKE', "%{$query}%")
            ->orWhere('code', 'LIKE', "%{$query}%")
            ->orWhere('department', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $subjects
        ]);
    }

    /**
     * Assign teachers to the subject for a specific class and session.
     */
    public function assignTeachers(AssignTeacherRequest $request, Subject $subject): JsonResponse
    {
        foreach ($request->user_ids as $userId) {
            SubjectAssignment::firstOrCreate([
                'user_id' => $userId,
                'subject_id' => $subject->id,
                'school_class_id' => $request->school_class_id,
                'academic_session_id' => $request->academic_session_id,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Teachers assigned successfully'
        ]);
    }

    /**
     * Unassign teachers from the subject for a specific class and session.
     */
    public function unassignTeachers(AssignTeacherRequest $request, Subject $subject): JsonResponse
    {
        SubjectAssignment::where('subject_id', $subject->id)
            ->whereIn('user_id', $request->user_ids)
            ->where('school_class_id', $request->school_class_id)
            ->where('academic_session_id', $request->academic_session_id)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Teachers unassigned successfully'
        ]);
    }

    /**
     * Get the teachers assigned to the subject.
     */
    public function teachers(Subject $subject): JsonResponse
    {
        $teachers = $subject->teachers()->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $teachers
        ]);
    }
}
