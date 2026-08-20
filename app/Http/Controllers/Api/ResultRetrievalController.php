<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\StudentTermResult;
use App\Models\StudentResult;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\Gate;

class ResultRetrievalController extends Controller
{
    /**
     * Get a single student's term result and subject breakdowns.
     */
    public function getStudentTermResult(Request $request, $student_id): JsonResponse
    {
        Gate::authorize('view-results');
        $this->authorizeStudentResultAccess($request->user(), $student_id);

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid|exists:academic_sessions,id',
            'term' => 'required|string',
        ]);

        $termResult = StudentTermResult::with(['academicSession', 'schoolClass', 'classArm'])
            ->where('student_id', $student_id)
            ->where('academic_session_id', $validated['academic_session_id'])
            ->where('term', $validated['term'])
            ->first();

        if (!$termResult) {
            return response()->json(['status' => 'error', 'message' => 'No term result found for this student.'], 404);
        }

        $subjectResults = StudentResult::with(['subject', 'details.resultConfiguration'])
            ->where('student_id', $student_id)
            ->where('academic_session_id', $validated['academic_session_id'])
            ->where('term', $validated['term'])
            ->orderBy('subject_position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'term_summary' => $termResult,
                'subjects' => $subjectResults
            ]
        ]);
    }

    /**
     * Get all term summaries for a class and arm.
     */
    public function getClassTermResults(Request $request, $school_class_id, $class_arm_id): JsonResponse
    {
        Gate::authorize('view-results');
        $this->authorizeClassAccess($request->user(), $school_class_id);

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid|exists:academic_sessions,id',
            'term' => 'required|string',
        ]);

        $termResults = StudentTermResult::with(['student'])
            ->where('school_class_id', $school_class_id)
            ->where('class_arm_id', $class_arm_id)
            ->where('academic_session_id', $validated['academic_session_id'])
            ->where('term', $validated['term'])
            ->orderBy('overall_class_position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $termResults
        ]);
    }

    /**
     * Generate the mastersheet for a class and arm.
     */
    public function getMastersheet(Request $request, $school_class_id, $class_arm_id): JsonResponse
    {
        Gate::authorize('view-results');
        $this->authorizeClassAccess($request->user(), $school_class_id);

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid|exists:academic_sessions,id',
            'term' => 'required|string',
        ]);

        // Fetch term results for the class to get the students and their summaries
        $termResults = StudentTermResult::with(['student'])
            ->where('school_class_id', $school_class_id)
            ->where('class_arm_id', $class_arm_id)
            ->where('academic_session_id', $validated['academic_session_id'])
            ->where('term', $validated['term'])
            ->orderBy('overall_class_position')
            ->get();

        // Fetch all subject results for this class/arm in this term
        $subjectResults = StudentResult::with(['subject'])
            ->where('school_class_id', $school_class_id)
            ->where('class_arm_id', $class_arm_id)
            ->where('academic_session_id', $validated['academic_session_id'])
            ->where('term', $validated['term'])
            ->get();

        // Determine unique subjects taken by this class arm
        $subjectsMap = [];
        foreach ($subjectResults as $result) {
            if ($result->subject) {
                $subjectsMap[$result->subject_id] = [
                    'id' => $result->subject->id,
                    'name' => $result->subject->name,
                    'code' => $result->subject->code
                ];
            }
        }
        $subjectsList = array_values($subjectsMap);

        // Group subject results by student_id
        $groupedResults = $subjectResults->groupBy('student_id');

        // Build the mastersheet grid
        $studentsGrid = [];
        foreach ($termResults as $tr) {
            $studentSubjectScores = [];
            $studentResults = $groupedResults->get($tr->student_id, collect());

            foreach ($studentResults as $sr) {
                $studentSubjectScores[$sr->subject_id] = [
                    'total_score' => $sr->total_score,
                    'grade' => $sr->grade,
                    'subject_position' => $sr->subject_position
                ];
            }

            $studentsGrid[] = [
                'student_id' => $tr->student_id,
                'name' => $tr->student ? ($tr->student->first_name . ' ' . $tr->student->last_name) : 'Unknown',
                'admission_number' => $tr->student ? $tr->student->admission_number : null,
                'total_score' => $tr->total_score,
                'average_score' => $tr->average_score,
                'class_arm_position' => $tr->class_arm_position,
                'overall_class_position' => $tr->overall_class_position,
                'subject_scores' => $studentSubjectScores
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'subjects' => $subjectsList,
                'students' => $studentsGrid
            ]
        ]);
    }

    /**
     * Get subject scores of all students in a class/arm (for a specific subject).
     */
    public function getClassSubjectScores(Request $request, $school_class_id, $class_arm_id, $subject_id): JsonResponse
    {
        Gate::authorize('view-results');
        $this->authorizeClassAccess($request->user(), $school_class_id);

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid|exists:academic_sessions,id',
            'term' => 'required|string',
        ]);

        $subjectResults = StudentResult::with(['student', 'details.resultConfiguration'])
            ->where('school_class_id', $school_class_id)
            ->where('class_arm_id', $class_arm_id)
            ->where('subject_id', $subject_id)
            ->where('academic_session_id', $validated['academic_session_id'])
            ->where('term', $validated['term'])
            ->orderBy('subject_position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $subjectResults
        ]);
    }

    /**
     * Helpers for specific row-level authorization
     */
    private function authorizeStudentResultAccess($user, $student_id)
    {
        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('student')) {
            $student = Student::where('user_id', $user->id)->first();
            if ($student && $student->id === $student_id) return true;
        }

        if ($user->hasRole(['parent', 'guardian'])) {
            $guardian = \App\Models\Guardian::where('user_id', $user->id)->first();
            if ($guardian && $guardian->students()->where('student_id', $student_id)->exists()) return true;
        }

        if ($user->hasRole('teacher')) {
            $student = Student::find($student_id);
            if ($student) {
                $assigned = \App\Models\SubjectAssignment::where('user_id', $user->id)
                    ->where('school_class_id', $student->school_class_id)
                    ->exists();
                if ($assigned) return true;
            }
        }

        abort(403, 'You are not authorized to view this result.');
    }

    private function authorizeClassAccess($user, $school_class_id)
    {
        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('teacher')) {
            $assigned = \App\Models\SubjectAssignment::where('user_id', $user->id)
                ->where('school_class_id', $school_class_id)
                ->exists();
            if ($assigned) return true;
        }

        abort(403, 'You are not authorized to view results for this class.');
    }
}
