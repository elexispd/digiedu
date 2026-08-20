<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Models\Student;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;

class StudentController extends Controller
{
    /**
     * Display a listing of the students.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('view-students');
        
        $query = Student::with(['user', 'guardians', 'schoolClass', 'classArm']);
        
        // Filter by search term (name or admission number)
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }
        
        // Filter by class name (e.g., "jss 1")
        if ($className = $request->input('class')) {
            $query->whereHas('schoolClass', function ($q) use ($className) {
                $q->where('name', 'like', "%{$className}%");
            });
        }
        
        // Filter by exact school_class_id if class_id is passed explicitly
        if ($classId = $request->input('class_id')) {
            $query->where('school_class_id', $classId);
        }

        // Simple pagination
        $perPage = $request->input('per_page', 15);
        $students = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => StudentResource::collection($students)->response()->getData(true)
        ]);
    }

    /**
     * Display the specified student.
     */
    public function show(string $id): JsonResponse
    {
        Gate::authorize('view-students');
        
        $student = Student::with(['user', 'guardians', 'documents', 'schoolClass', 'classArm'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => new StudentResource($student)
        ]);
    }

    /**
     * Update the specified student in storage.
     */
    public function update(UpdateStudentRequest $request, string $id): JsonResponse
    {
        Gate::authorize('update-students');
        
        $student = Student::findOrFail($id);
        
        // Update the student with the validated data
        $student->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => new StudentResource($student->fresh(['user', 'guardians', 'schoolClass', 'classArm']))
        ]);
    }

    /**
     * Remove the specified student from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('delete-students');
        
        $student = Student::findOrFail($id);

        // Delete associated user account if it exists
        if ($student->user_id) {
            $user = User::find($student->user_id);
            if ($user) {
                $user->delete();
            }
        }

        $student->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Student and linked user account deleted successfully'
        ]);
    }
}
