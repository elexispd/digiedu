<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Models\StudentAttendance;
use App\Models\Student;
use Carbon\Carbon;

class StudentAttendanceController extends Controller
{
    /**
     * Mark single or multiple students' attendance for a specific date and class.
     */
    public function markAttendance(Request $request): JsonResponse
    {
        Gate::authorize('manage-attendance');

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid|exists:academic_sessions,id',
            'term' => 'required|string',
            'date' => 'required|date',
            'school_class_id' => 'required|uuid|exists:school_classes,id',
            'class_arm_id' => 'required|uuid|exists:class_arms,id',
            'students' => 'required|array',
            'students.*.student_id' => 'required|uuid|exists:students,id',
            'students.*.status' => 'required|in:present,absent,late,excused',
            'students.*.remark' => 'nullable|string',
        ]);

        $commonData = [
            'academic_session_id' => $validated['academic_session_id'],
            'term' => $validated['term'],
            'date' => $validated['date'],
            'school_class_id' => $validated['school_class_id'],
            'class_arm_id' => $validated['class_arm_id'],
        ];

        $records = [];
        foreach ($validated['students'] as $studentData) {
            $record = StudentAttendance::updateOrCreate(
                [
                    'student_id' => $studentData['student_id'],
                    'date' => $validated['date'],
                ],
                array_merge($commonData, [
                    'status' => $studentData['status'],
                    'remark' => $studentData['remark'] ?? null,
                ])
            );
            $records[] = $record;
        }

        $eloquentCollection = new \Illuminate\Database\Eloquent\Collection($records);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance marked successfully',
            'data' => $eloquentCollection->load('student')
        ]);
    }

    /**
     * Get attendance metrics for a specific class/arm on a specific date.
     */
    public function metrics(Request $request): JsonResponse
    {
        Gate::authorize('view-attendance');

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid',
            'term' => 'required|string',
            'date' => 'required|date',
            'school_class_id' => 'required|uuid',
            'class_arm_id' => 'required|uuid',
        ]);

        // Get total students currently assigned to this class and arm
        $totalStudents = Student::where('school_class_id', $validated['school_class_id'])
            ->where('class_arm_id', $validated['class_arm_id'])
            ->count();

        // Get attendance records for this criteria
        $attendances = StudentAttendance::where([
            'academic_session_id' => $validated['academic_session_id'],
            'term' => $validated['term'],
            'date' => $validated['date'],
            'school_class_id' => $validated['school_class_id'],
            'class_arm_id' => $validated['class_arm_id'],
        ])->get();

        $presentCount = $attendances->where('status', 'present')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $excusedCount = $attendances->where('status', 'excused')->count();
        
        $denominator = max($totalStudents, $attendances->count());
        $denominator = $denominator > 0 ? $denominator : 1; // avoid division by zero

        $metrics = [
            'present' => [
                'count' => $presentCount,
                'percentage' => round(($presentCount / $denominator) * 100, 2)
            ],
            'absent' => [
                'count' => $absentCount,
                'percentage' => round(($absentCount / $denominator) * 100, 2)
            ],
            'late' => [
                'count' => $lateCount,
                'percentage' => round(($lateCount / $denominator) * 100, 2)
            ],
            'excused' => [
                'count' => $excusedCount,
                'percentage' => round(($excusedCount / $denominator) * 100, 2)
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_students_in_class' => $totalStudents,
                'metrics' => $metrics
            ]
        ]);
    }

    /**
     * Get existing attendance records.
     */
    public function records(Request $request): JsonResponse
    {
        Gate::authorize('view-attendance');

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid',
            'term' => 'required|string',
            'date' => 'required|date',
            'school_class_id' => 'required|uuid',
            'class_arm_id' => 'required|uuid',
        ]);

        $query = StudentAttendance::with('student')->where($validated);

        if ($search = $request->input('search')) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $attendances = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $attendances
        ]);
    }

    /**
     * Get weekly attendance data for graph plotting.
     */
    public function weeklyMetrics(Request $request): JsonResponse
    {
        Gate::authorize('view-attendance');

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid',
            'term' => 'required|string',
            'date' => 'required|date',
            'school_class_id' => 'required|uuid',
            'class_arm_id' => 'required|uuid',
        ]);

        $date = Carbon::parse($validated['date']);
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek = $date->copy()->endOfWeek();

        $attendances = StudentAttendance::where([
            'academic_session_id' => $validated['academic_session_id'],
            'term' => $validated['term'],
            'school_class_id' => $validated['school_class_id'],
            'class_arm_id' => $validated['class_arm_id'],
        ])
        ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
        ->get();

        $graphData = [];
        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i)->format('Y-m-d');
            
            $dayRecords = $attendances->where('date', $currentDay);
            
            $graphData[] = [
                'date' => $currentDay,
                'day_name' => $startOfWeek->copy()->addDays($i)->format('l'),
                'present' => $dayRecords->where('status', 'present')->count(),
                'absent' => $dayRecords->where('status', 'absent')->count(),
                'late' => $dayRecords->where('status', 'late')->count(),
                'excused' => $dayRecords->where('status', 'excused')->count(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $graphData
        ]);
    }

    /**
     * Get students on the attendance watchlist (< 50% attendance).
     */
    public function watchlist(Request $request): JsonResponse
    {
        Gate::authorize('view-attendance');

        $validated = $request->validate([
            'academic_session_id' => 'required|uuid',
            'term' => 'required|string',
            'school_class_id' => 'required|uuid',
            'class_arm_id' => 'required|uuid',
        ]);

        $attendances = StudentAttendance::with(['student.schoolClass', 'student.classArm'])
            ->where([
                'academic_session_id' => $validated['academic_session_id'],
                'term' => $validated['term'],
                'school_class_id' => $validated['school_class_id'],
                'class_arm_id' => $validated['class_arm_id'],
            ])
            ->get();

        $grouped = $attendances->groupBy('student_id');

        $watchlist = [];

        foreach ($grouped as $studentId => $records) {
            $totalDays = $records->count();
            $presentDays = $records->whereIn('status', ['present', 'late'])->count();
            
            $percentage = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;

            if ($percentage < 50) {
                $student = $records->first()->student;
                if ($student) {
                    $watchlist[] = [
                        'student_id' => $student->id,
                        'name' => trim("{$student->first_name} {$student->middle_name} {$student->last_name}"),
                        'reg_number' => $student->admission_number,
                        'class' => $student->schoolClass ? $student->schoolClass->name : null,
                        'class_arm' => $student->classArm ? $student->classArm->name : null,
                        'attendance_percentage' => round($percentage, 2)
                    ];
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $watchlist
        ]);
    }
}
