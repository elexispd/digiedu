<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Models\SchoolClass;
use App\Models\Student;

class TeacherPortalController extends Controller
{
    /**
     * Get the teacher's assigned classes with their subjects and counts.
     */
    public function myClasses(Request $request): JsonResponse
    {
        Gate::authorize('view-classes');

        $teacher = $request->user();

        // 1. Get all subject assignments for this teacher, eager loading subject and schoolClass with arms
        $assignments = \App\Models\SubjectAssignment::where('user_id', $teacher->id)
            ->with([
                'subject:id,name,code',
                'schoolClass:id,name,category',
                'schoolClass.arms:id,school_class_id,name',
            ])
            ->get();

        // 2. Group the assignments by class
        $classesMap = [];
        foreach ($assignments as $assignment) {
            if (!$assignment->schoolClass || !$assignment->subject) {
                continue;
            }

            $classId = $assignment->school_class_id;
            
            if (!isset($classesMap[$classId])) {
                $classesMap[$classId] = [
                    'schoolClass' => $assignment->schoolClass,
                    'subjects' => [],
                ];
            }

            // Avoid duplicate subjects if there happen to be any
            $subjectExists = false;
            foreach ($classesMap[$classId]['subjects'] as $existingSubj) {
                if ($existingSubj['id'] === $assignment->subject->id) {
                    $subjectExists = true;
                    break;
                }
            }

            if (!$subjectExists) {
                $classesMap[$classId]['subjects'][] = [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                    'code' => $assignment->subject->code,
                ];
            }
        }

        $classIds = array_keys($classesMap);

        // Query student counts grouped by class
        $classStudentCounts = count($classIds) > 0
            ? Student::whereIn('school_class_id', $classIds)
                ->groupBy('school_class_id')
                ->selectRaw('school_class_id, count(*) as total')
                ->pluck('total', 'school_class_id')
            : collect();

        // Query student counts grouped by class arm
        $armStudentCounts = count($classIds) > 0
            ? Student::whereIn('school_class_id', $classIds)
                ->whereNotNull('class_arm_id')
                ->groupBy('class_arm_id')
                ->selectRaw('class_arm_id, count(*) as total')
                ->pluck('total', 'class_arm_id')
            : collect();

        // Query overall attendance stats grouped by school_class_id
        $classAttendanceStats = count($classIds) > 0
            ? \App\Models\StudentAttendance::whereIn('school_class_id', $classIds)
                ->selectRaw('school_class_id, count(*) as total_records, sum(case when status in ("present", "late") then 1 else 0 end) as present_records')
                ->groupBy('school_class_id')
                ->get()
                ->keyBy('school_class_id')
            : collect();

        // Build the formatted classes response
        $classes = [];
        foreach ($classesMap as $classId => $item) {
            $schoolClass = $item['schoolClass'];
            $classCount = (int) ($classStudentCounts[$classId] ?? 0);

            $attStat = $classAttendanceStats->get($classId);
            $totalRecords = $attStat ? (int) $attStat->total_records : 0;
            $presentRecords = $attStat ? (int) $attStat->present_records : 0;
            $classAttendancePercentage = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 1) : 0.0;

            $classes[] = [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'category' => $schoolClass->category,
                'total_students' => $classCount,
                'no_of_students' => $classCount,
                'attendance_percentage' => $classAttendancePercentage,
                'total_attendance_percentage' => $classAttendancePercentage,
                'arms' => $schoolClass->arms->map(fn($arm) => [
                    'id' => $arm->id,
                    'name' => $arm->name,
                    'total_students' => (int) ($armStudentCounts[$arm->id] ?? 0),
                    'no_of_students' => (int) ($armStudentCounts[$arm->id] ?? 0),
                ])->values(),
                'subjects' => $item['subjects'],
            ];
        }

        // 3. Get total classes
        $totalClasses = count($classes);

        // 4. Get overall total students in those classes
        $totalStudents = count($classIds) > 0 
            ? Student::whereIn('school_class_id', $classIds)->count() 
            : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'classes' => $classes,
                'counts' => [
                    'total_classes' => $totalClasses,
                    'total_students' => $totalStudents,
                ]
            ]
        ]);
    }

    /**
     * Get detailed analytics for a single assigned class.
     * Includes top_of_the_class (highest attendance) and needs_attention (lowest attendance).
     */
    public function showClass(Request $request, string $id): JsonResponse
    {
        Gate::authorize('view-classes');

        $user = $request->user();

        // Verify class exists
        $schoolClass = SchoolClass::with('arms:id,school_class_id,name')->find($id);
        if (!$schoolClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Class not found'
            ], 404);
        }

        // Authorize teacher assignment: admin or lead teacher or assigned to teach a subject
        $isAssigned = $user->hasRole('admin')
            || $schoolClass->lead_teacher_id === $user->id
            || \App\Models\SubjectAssignment::where('user_id', $user->id)
                ->where('school_class_id', $id)
                ->exists();

        if (!$isAssigned) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not assigned to this class'
            ], 403);
        }

        // Get subjects taught in this class by the teacher (or all class subjects if admin)
        $subjectsQuery = \App\Models\SubjectAssignment::where('school_class_id', $id)
            ->with('subject:id,name,code');

        if (!$user->hasRole('admin')) {
            $subjectsQuery->where('user_id', $user->id);
        }

        $subjects = $subjectsQuery->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn($subj) => [
                'id' => $subj->id,
                'name' => $subj->name,
                'code' => $subj->code,
            ]);

        // Fetch students for this class
        $students = Student::where('school_class_id', $id)
            ->with('classArm:id,name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'admission_number', 'photo', 'class_arm_id']);

        $totalStudents = $students->count();

        // Student counts per arm
        $armStudentCounts = Student::where('school_class_id', $id)
            ->whereNotNull('class_arm_id')
            ->groupBy('class_arm_id')
            ->selectRaw('class_arm_id, count(*) as total')
            ->pluck('total', 'class_arm_id');

        // Aggregated attendance stats for students in this class
        $attendanceStats = \App\Models\StudentAttendance::where('school_class_id', $id)
            ->selectRaw('student_id, count(*) as total_days, sum(case when status in ("present", "late") then 1 else 0 end) as present_days')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        // Total class attendance percentage
        $totalAttendanceRecords = $attendanceStats->sum('total_days');
        $presentAttendanceRecords = $attendanceStats->sum('present_days');
        $totalAttendancePercentage = $totalAttendanceRecords > 0
            ? round(($presentAttendanceRecords / $totalAttendanceRecords) * 100, 1)
            : 0.0;

        // Rank students by attendance
        $rankedStudents = $students->map(function ($student) use ($attendanceStats) {
            $stat = $attendanceStats->get($student->id);
            $totalDays = $stat ? (int) $stat->total_days : 0;
            $presentDays = $stat ? (int) $stat->present_days : 0;
            $percentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0.0;

            $fullName = preg_replace('/\s+/', ' ', trim("{$student->first_name} {$student->middle_name} {$student->last_name}"));

            return [
                'id' => $student->id,
                'name' => $fullName,
                'percentage' => $percentage,
            ];
        });

        // Top 3 students with highest attendance
        $topOfTheClass = $rankedStudents
            ->sortByDesc('percentage')
            ->values()
            ->take(3)
            ->toArray();

        // Top 3 students with lowest attendance
        $needsAttention = $rankedStudents
            ->sortBy('percentage')
            ->values()
            ->take(3)
            ->toArray();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'category' => $schoolClass->category,
                'total_students' => $totalStudents,
                'no_of_students' => $totalStudents,
                'attendance_percentage' => $totalAttendancePercentage,
                'total_attendance_percentage' => $totalAttendancePercentage,
                'arms' => $schoolClass->arms->map(fn($arm) => [
                    'id' => $arm->id,
                    'name' => $arm->name,
                    'total_students' => (int) ($armStudentCounts[$arm->id] ?? 0),
                    'no_of_students' => (int) ($armStudentCounts[$arm->id] ?? 0),
                ])->values(),
                'subjects' => $subjects,
                'top_of_the_class' => $topOfTheClass,
                'needs_attention' => $needsAttention,
            ]
        ]);
    }

    /**
     * Get list of students in an assigned class (with optional arm filtering and search).
     * Returns: id, name, reg_number, class_arm_id, class_arm
     */
    public function classStudents(Request $request, string $id): JsonResponse
    {
        Gate::authorize('view-classes');

        $user = $request->user();

        $schoolClass = SchoolClass::find($id);
        if (!$schoolClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Class not found'
            ], 404);
        }

        // Authorize teacher assignment: admin or lead teacher or assigned to teach a subject in this class
        $isAssigned = $user->hasRole('admin')
            || $schoolClass->lead_teacher_id === $user->id
            || \App\Models\SubjectAssignment::where('user_id', $user->id)
                ->where('school_class_id', $id)
                ->exists();

        if (!$isAssigned) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not assigned to this class'
            ], 403);
        }

        $query = Student::where('school_class_id', $id);

        if ($armParam = $request->input('class_arm_id') ?? $request->input('arm_id') ?? $request->input('class_arm') ?? $request->input('arm')) {
            $query->where(function ($q) use ($armParam) {
                $q->where('class_arm_id', $armParam)
                  ->orWhereHas('classArm', function ($armQ) use ($armParam) {
                      $armQ->where('name', $armParam);
                  });
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('first_name')
            ->orderBy('last_name')
            ->with('classArm:id,name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'admission_number', 'class_arm_id'])
            ->map(function ($student) {
                $fullName = preg_replace('/\s+/', ' ', trim("{$student->first_name} {$student->middle_name} {$student->last_name}"));

                return [
                    'id' => $student->id,
                    'name' => $fullName,
                    'reg_number' => $student->admission_number,
                    'class_arm_id' => $student->class_arm_id,
                    'class_arm' => $student->classArm ? [
                        'id' => $student->classArm->id,
                        'name' => $student->classArm->name,
                    ] : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $students
        ]);
    }

    /**
     * Get the logged-in teacher's weekly teaching timetable.
     */
    public function myTimetable(Request $request): JsonResponse
    {
        Gate::authorize('view-timetable');

        $teacher = $request->user();
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $periods = \App\Models\TimetablePeriod::orderBy('order', 'asc')->get();

        $currentSession = \App\Models\AcademicSession::where('is_current', true)->first();
        $sessionId = $request->query('academic_session_id') ?? $currentSession?->id;
        $rawTerm = $request->query('term') ?? $currentSession?->current_term ?? 1;
        $term = (int) ($rawTerm instanceof \App\Enums\TermEnum ? $rawTerm->value : $rawTerm);

        $slots = \App\Models\TimetableSlot::where('teacher_id', $teacher->id)
            ->where('academic_session_id', $sessionId)
            ->where('term', $term)
            ->with([
                'schoolClass:id,name,category',
                'classArm:id,school_class_id,name',
                'subject:id,name,code',
                'period:id,name,period_number,start_time,end_time,is_break,order',
            ])
            ->get();

        $slotsGrouped = [];
        foreach ($slots as $slot) {
            $key = $slot->day_of_week . '_' . $slot->timetable_period_id;
            $slotsGrouped[$key] = [
                'id' => $slot->id,
                'class_name' => ($slot->schoolClass?->name ?? '') . ' ' . ($slot->classArm?->name ?? ''),
                'school_class_id' => $slot->school_class_id,
                'class_arm_id' => $slot->class_arm_id,
                'subject_id' => $slot->subject_id,
                'subject_name' => $slot->subject?->name ?? '',
                'room' => $slot->room ?? 'Room 101',
                'session_type' => strtoupper($slot->session_type ?? 'lecture'),
            ];
        }

        $grid = [];
        foreach ($periods as $period) {
            $row = [
                'period_id' => $period->id,
                'period_name' => $period->name,
                'period_number' => $period->period_number,
                'time' => $period->start_time . ' - ' . $period->end_time,
                'is_break' => $period->is_break,
                'days' => [],
            ];

            foreach ($days as $day) {
                $key = $day . '_' . $period->id;
                $row['days'][$day] = $slotsGrouped[$key] ?? null;
            }

            $grid[] = $row;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'teacher' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                ],
                'session' => [
                    'academic_session_id' => $sessionId,
                    'term' => $term,
                ],
                'periods' => $periods,
                'grid' => $grid,
            ],
        ]);
    }
}

