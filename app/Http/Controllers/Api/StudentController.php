<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\GradeRule;
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
        
        $query = Student::with([
            'user:id,name,email,status',
            'guardians:id,first_name,last_name,phone_number',
            'schoolClass:id,name,category',
            'classArm:id,name',
            'termResults.schoolClass:id,name',
            'termResults.academicSession:id,name',
            'termResults.classArm:id,name',
        ]);
        
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
        if ($className = $request->input('class') ?? $request->input('class_name')) {
            $query->whereHas('schoolClass', function ($q) use ($className) {
                $q->where('name', 'like', "%{$className}%");
            });
        }
        
        // Filter by exact school_class_id if class_id is passed explicitly
        if ($classId = $request->input('class_id') ?? $request->input('school_class_id')) {
            $query->where('school_class_id', $classId);
        }

        // Filter by class arm (optional)
        if ($armParam = $request->input('class_arm_id') ?? $request->input('arm_id') ?? $request->input('class_arm') ?? $request->input('arm')) {
            $query->where(function ($q) use ($armParam) {
                $q->where('class_arm_id', $armParam)
                  ->orWhereHas('classArm', function ($armQ) use ($armParam) {
                      $armQ->where('name', $armParam);
                  });
            });
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
        
        $student = Student::with([
            'user:id,name,email,status',
            'guardians:id,first_name,last_name,phone_number',
            'documents:id,student_id,document_type,file_path,original_name',
            'schoolClass:id,name,category',
            'classArm:id,name',
            'termResults.schoolClass:id,name',
            'termResults.academicSession:id,name',
            'termResults.classArm:id,name',
        ])->findOrFail($id);

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
            'data' => new StudentResource($student->fresh([
                'user:id,name,email,status',
                'guardians:id,first_name,last_name,phone_number',
                'schoolClass:id,name,category',
                'classArm:id,name',
                'termResults.schoolClass:id,name',
                'termResults.academicSession:id,name',
                'termResults.classArm:id,name',
            ]))
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

    /**
     * Student overview: parent info, attendance summary, academic performance.
     */
    public function overview(Request $request, string $id): JsonResponse
    {
        Gate::authorize('view-students');

        $student = Student::with([
            'guardians:id,first_name,last_name,phone_number,email,occupation',
            'schoolClass:id,name,category',
        ])->findOrFail($id);

        // Resolve session & term
        $session = AcademicSession::where('is_current', true)->first();
        $term = $request->input('term', $session?->current_term?->value ?? $session?->current_term);
        $sessionId = $request->input('academic_session_id', $session?->id);

        // --- Attendance overview ---
        $attendances = $student->attendances()
            ->where('academic_session_id', $sessionId)
            ->where('term', $term)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $present  = (int) ($attendances['present']  ?? 0);
        $absent   = (int) ($attendances['absent']   ?? 0);
        $late     = (int) ($attendances['late']     ?? 0);
        $total    = $present + $absent + $late;

        // --- Academic performance ---
        $termResult = $student->termResults()
            ->where('academic_session_id', $sessionId)
            ->where('term', $term)
            ->first();

        $averageScore = $termResult?->average_score;

        // Count total students in same class arm for rank display
        $classTotal = $termResult
            ? \App\Models\StudentTermResult::where('academic_session_id', $sessionId)
                ->where('term', $term)
                ->where('class_arm_id', $termResult->class_arm_id)
                ->count()
            : null;

        // Resolve grade from grade_rules based on student's class category
        $grade = null;
        if ($averageScore !== null && $student->schoolClass) {
            $grade = GradeRule::where('class_category', $student->schoolClass->category)
                ->where('min_score', '<=', $averageScore)
                ->where('max_score', '>=', $averageScore)
                ->first(['grade', 'remark']);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'admission_number' => $student->admission_number,
                'parents' => $student->guardians->map(fn($g) => [
                    'id'           => $g->id,
                    'name'         => $g->first_name . ' ' . $g->last_name,
                    'phone_number' => $g->phone_number,
                    'email'        => $g->email,
                    'occupation'   => $g->occupation,
                    'relationship' => $g->pivot->relationship,
                ]),
                'attendance' => [
                    'term'    => $term,
                    'session' => $session?->name,
                    'total'   => $total,
                    'present' => $present,
                    'absent'  => $absent,
                    'late'    => $late,
                ],
                'academic_performance' => [
                    'term'          => $term,
                    'session'       => $session?->name,
                    'average_score' => $averageScore !== null ? round($averageScore, 1) : null,
                    'class_rank'    => $termResult?->class_arm_position,
                    'class_total'   => $classTotal,
                    'grade'         => $grade?->grade,
                    'remark'        => $grade?->remark,
                ],
            ]
        ]);
    }

    /**
     * Get paginated attendance records for a single student.
     */
    public function attendance(Request $request, string $id): JsonResponse
    {
        Gate::authorize('view-students');

        $student = Student::findOrFail($id);

        $session = AcademicSession::where('is_current', true)->first();

        $query = $student->attendances()
            ->where('academic_session_id', $request->input('academic_session_id', $session?->id))
            ->where('term', $request->input('term', $session?->current_term?->value ?? $session?->current_term));

        if ($date = $request->input('date')) {
            $query->whereDate('date', $date);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $records = $query->orderBy('date', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $records
        ]);
    }

    /**
     * Display the badges awarded to a specified student.
     */
    public function badges(string $id): JsonResponse
    {
        Gate::authorize('view-students');
        
        $student = Student::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $student->badges()->get()
        ]);
    }

    /**
     * Assign a badge to a student.
     */
    public function assignBadge(Request $request, string $id): JsonResponse
    {
        Gate::authorize('assign-badges');

        $request->validate([
            'badge_id' => 'required|exists:badges,id'
        ]);

        $student = Student::findOrFail($id);
        
        // Prevent assigning the same badge multiple times
        if ($student->badges()->where('badge_id', $request->badge_id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Student already has this badge.'
            ], 400);
        }

        $student->badges()->attach($request->badge_id, [
            'awarded_by' => $request->user()->id, // Storing the admin's user ID
            'awarded_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Badge assigned to student successfully.'
        ]);
    }

    /**
     * Revoke a badge from a student.
     * Admins can revoke any badge; teachers can only revoke badges they awarded.
     */
    public function revokeBadge(Request $request, string $id, string $badgeId): JsonResponse
    {
        Gate::authorize('assign-badges');

        $student = Student::findOrFail($id);

        $pivot = $student->badges()->wherePivot('badge_id', $badgeId)->first();

        if (!$pivot) {
            return response()->json(['status' => 'error', 'message' => 'Badge not found on this student.'], 404);
        }

        if ($request->user()->hasRole('teacher') && $pivot->pivot->awarded_by !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'You can only revoke badges you awarded.'], 403);
        }

        $student->badges()->detach($badgeId);

        return response()->json([
            'status' => 'success',
            'message' => 'Badge revoked from student successfully.'
        ]);
    }
}
