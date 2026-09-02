<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class GuardianPortalController extends Controller
{
    /**
     * Send a 7-digit activation code to the guardian's email/phone.
     */
    public function sendActivationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required_without:phone|email|nullable',
            'phone' => 'required_without:email|string|nullable'
        ]);

        $user = \App\Models\User::where(function ($query) use ($validated) {
            if (!empty($validated['email'])) {
                $query->orWhere('email', $validated['email']);
            }
            if (!empty($validated['phone'])) {
                $query->orWhere('phone', $validated['phone']);
            }
        })->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        if ($user->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is already activated.'
            ], 400);
        }

        // Generate 7-digit code
        $code = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);

        // Store code in cache for 15 minutes
        $cacheKey = 'guardian_activation_code_' . $user->id;
        \Illuminate\Support\Facades\Cache::put($cacheKey, $code, now()->addMinutes(15));

        if (!empty($validated['email'])) {
            try {
                \Illuminate\Support\Facades\Mail::to($validated['email'])->send(new \App\Mail\ActivationOtpMail($code));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Mail failed: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Activation code sent to your email successfully.'
            ]);
        }

        if (!empty($validated['phone'])) {
            return response()->json([
                'status' => 'success',
                'message' => 'Activation code generated for phone successfully.',
                'data' => [
                    'code' => $code 
                ]
            ]);
        }
        
        return response()->json(['status' => 'error', 'message' => 'Invalid request'], 400);
    }

    /**
     * Verify the activation code.
     */
    public function verifyActivationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required_without:phone|email|nullable',
            'phone' => 'required_without:email|string|nullable',
            'code' => 'required|string|size:7'
        ]);

        $user = \App\Models\User::where(function ($query) use ($validated) {
            if (!empty($validated['email'])) {
                $query->orWhere('email', $validated['email']);
            }
            if (!empty($validated['phone'])) {
                $query->orWhere('phone', $validated['phone']);
            }
        })->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        if ($user->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is already activated.'
            ], 400);
        }

        $cacheKey = 'guardian_activation_code_' . $user->id;
        $cachedCode = Cache::get($cacheKey);

        if (!$cachedCode || $cachedCode !== $validated['code']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired activation code.'
            ], 400);
        }

        // Clear code from cache
        Cache::forget($cacheKey);

        // Create Sanctum Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Activation code verified successfully.',
            'data' => [
                'token' => $token,
                'user' => $user->load('roles')
            ]
        ]);
    }

    /**
     * Reset password and activate account.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is already activated.'
            ], 400);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()]
        ]);

        // Update password and status
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'status' => 'active'
        ])->save();

        if ($user->guardian) {
            $user->guardian->forceFill(['status' => 'active'])->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Account activated successfully. Password has been updated.'
        ]);
    }

    /**
     * Update notification preferences for the guardian.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json([
                'status' => 'error',
                'message' => 'Guardian profile not found for this user.'
            ], 404);
        }

        $validated = $request->validate([
            'grades_reports' => 'boolean',
            'behavior_alerts' => 'boolean',
            'attendance' => 'boolean',
            'school_announcements' => 'boolean',
        ]);

        $guardian->update([
            'notification_preferences' => $validated
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification preferences updated successfully.',
            'data' => $guardian->fresh()->notification_preferences
        ]);
    }

    /**
     * Get all students linked to the guardian.
     */
    public function myStudents(Request $request): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json([
                'status' => 'error',
                'message' => 'Guardian profile not found for this user.'
            ], 404);
        }

        $students = $guardian->students()
            ->with([
                'user:id,name,email,status',
                'guardians:id,first_name,last_name,phone_number',
                'schoolClass:id,name,category',
                'classArm:id,name',
                'termResults.schoolClass:id,name',
                'termResults.academicSession:id,name',
                'termResults.classArm:id,name',
            ])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => StudentResource::collection($students)
        ]);
    }

    /**
     * Get dashboard data for a specific student linked to this guardian.
     */
    public function studentDashboard(Request $request, string $studentId): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json(['status' => 'error', 'message' => 'Guardian profile not found.'], 404);
        }

        // Verify the student belongs to this guardian
        $student = $guardian->students()->where('students.id', $studentId)->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found or not linked to your account.'], 403);
        }

        // Calculate attendance percentage
        $totalAttendanceRecords = \App\Models\StudentAttendance::where('student_id', $student->id)->count();
        $presentRecords = \App\Models\StudentAttendance::where('student_id', $student->id)
            ->where('status', 'present')
            ->count();

        $attendancePercentage = 0;
        if ($totalAttendanceRecords > 0) {
            $attendancePercentage = round(($presentRecords / $totalAttendanceRecords) * 100, 2);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'student_id' => $student->id,
                'student_name' => trim($student->first_name . ' ' . $student->last_name),
                'gpa_score' => '3.8', // Dummy
                'gpa_remark' => 'Excellent', // Dummy
                'average' => '85.5', // Dummy
                'attendance_percentage' => $attendancePercentage,
            ]
        ]);
    }

    /**
     * Get badges assigned to a specific student.
     */
    public function studentBadges(Request $request, string $studentId): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json(['status' => 'error', 'message' => 'Guardian profile not found.'], 404);
        }

        // Verify the student belongs to this guardian
        $student = $guardian->students()->where('students.id', $studentId)->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found or not linked to your account.'], 403);
        }

        // Load the student's badges with pivot data (awarded_by, awarded_at)
        $badges = $student->badges()->get();

        return response()->json([
            'status' => 'success',
            'data' => $badges
        ]);
    }

    /**
     * Get behavior/conduct logs for a specific student.
     */
    public function studentBehaviors(Request $request, string $studentId): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json(['status' => 'error', 'message' => 'Guardian profile not found.'], 404);
        }

        // Verify the student belongs to this guardian
        $student = $guardian->students()->where('students.id', $studentId)->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found or not linked to your account.'], 403);
        }

        // Fetch behavior logs with filtering
        $query = \App\Models\ConductLog::where('student_id', $student->id)
            ->with('teacher:id,name');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_range')) {
            $range = $request->date_range;
            if ($range === '7days') {
                $query->where('date', '>=', now()->subDays(7));
            } elseif ($range === '1month') {
                $query->where('date', '>=', now()->subMonth());
            } elseif ($range === '3months') {
                $query->where('date', '>=', now()->subMonths(3));
            }
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $behaviors = $query->orderBy('date', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $behaviors
        ]);
    }

    /**
     * Get academic history (list of sessions, terms, and classes) where the student has results.
     */
    public function studentAcademicHistory(Request $request, string $studentId): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json(['status' => 'error', 'message' => 'Guardian profile not found.'], 404);
        }

        $student = $guardian->students()->where('students.id', $studentId)->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found or not linked to your account.'], 403);
        }

        // Fetch distinct history from term results
        $history = \App\Models\StudentTermResult::with(['academicSession:id,name', 'schoolClass:id,name', 'classArm:id,name'])
            ->where('student_id', $student->id)
            ->where('status', 'published') // Only show terms where results are published
            ->select('academic_session_id', 'term', 'school_class_id', 'class_arm_id')
            ->distinct()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    /**
     * Get academic performance for a specific student (Dummy data for now).
     */
    public function studentAcademicPerformance(Request $request, string $studentId): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json(['status' => 'error', 'message' => 'Guardian profile not found.'], 404);
        }

        // Verify the student belongs to this guardian
        $student = $guardian->students()->where('students.id', $studentId)->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found or not linked to your account.'], 403);
        }

        // Filters that the frontend can send
        $term = $request->query('term', 'First Term');
        $subjectId = $request->query('subject_id');
        
        // In the future, you will query actual Result/Grade models using $request->class_id and $request->arm_id

        $dummyData = collect([
            [
                'subject_id' => 'dummy-math-uuid',
                'subject_name' => 'Mathematics',
                'percentage' => 68,
                'term' => $term,
                'grade' => 'B'
            ],
            [
                'subject_id' => 'dummy-science-uuid',
                'subject_name' => 'Basic Science',
                'percentage' => 87,
                'term' => $term,
                'grade' => 'A'
            ],
            [
                'subject_id' => 'dummy-english-uuid',
                'subject_name' => 'English Language',
                'percentage' => 74,
                'term' => $term,
                'grade' => 'B'
            ],
            [
                'subject_id' => 'dummy-history-uuid',
                'subject_name' => 'History',
                'percentage' => 52,
                'term' => $term,
                'grade' => 'C'
            ]
        ]);

        if ($subjectId) {
            $dummyData = $dummyData->where('subject_id', $subjectId)->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $dummyData
        ]);
    }

    /**
     * Get attendance data for a specific student.
     */
    public function studentAttendance(Request $request, string $studentId): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json(['status' => 'error', 'message' => 'Guardian profile not found.'], 404);
        }

        // Verify the student belongs to this guardian
        $student = $guardian->students()->where('students.id', $studentId)->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found or not linked to your account.'], 403);
        }

        // Optional filters for term and session
        $query = \App\Models\StudentAttendance::where('student_id', $student->id);

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }
        if ($request->filled('academic_session_id')) {
            $query->where('academic_session_id', $request->academic_session_id);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        $totalRecords = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $excused = $attendances->where('status', 'excused')->count();

        // Calculate percentage (Present + Late usually count as attended, but let's just use 'present' and 'late' together, or just 'present' based on strict rules. We'll use present + late as attended if they were at school)
        $attendedCount = $present + $late;
        $percentage = $totalRecords > 0 ? round(($attendedCount / $totalRecords) * 100, 2) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'percentage' => $percentage,
                    'total_present' => $present,
                    'total_late' => $late,
                    'total_absent' => $absent,
                    'total_excused' => $excused,
                    'total_records' => $totalRecords,
                ],
                'records' => $attendances
            ]
        ]);
    }

    /**
     * Get announcements targeted at guardians/parents.
     */
    public function announcements(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $announcements = \App\Models\Announcement::where(function($query) {
            $query->whereJsonContains('audience_target', 'parent')
                  ->orWhereJsonContains('audience_target', 'guardian')
                  ->orWhereJsonContains('audience_target', 'all');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        // Map is_read status
        $announcements->getCollection()->transform(function ($announcement) use ($user) {
            $isRead = $announcement->viewers()->where('user_id', $user->id)->exists();
            $announcement->is_read = $isRead;
            return $announcement;
        });

        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }

    /**
     * Mark a single announcement as read.
     */
    public function markAnnouncementRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $announcement = \App\Models\Announcement::findOrFail($id);

        if (!$announcement->viewers()->where('user_id', $user->id)->exists()) {
            $announcement->viewers()->attach($user->id, [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'viewed_at' => now()
            ]);
            $announcement->increment('total_views');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement marked as read.'
        ]);
    }

    /**
     * Mark all announcements as read.
     */
    public function markAllAnnouncementsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $announcements = \App\Models\Announcement::where(function($query) {
            $query->whereJsonContains('audience_target', 'parent')
                  ->orWhereJsonContains('audience_target', 'guardian')
                  ->orWhereJsonContains('audience_target', 'all');
        })->get();

        foreach ($announcements as $announcement) {
            if (!$announcement->viewers()->where('user_id', $user->id)->exists()) {
                // Assuming announcement_user_views doesn't strictly require a UUID if auto-increment, but let's provide one just in case the pivot is a model
                $announcement->viewers()->attach($user->id, [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'viewed_at' => now()
                ]);
                $announcement->increment('total_views');
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'All announcements marked as read.'
        ]);
    }

    /**
     * Get announcement counts (unread and total) for the guardian.
     */
    public function announcementsCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = \App\Models\Announcement::where(function ($q) {
            $q->whereJsonContains('audience_target', 'parent')
              ->orWhereJsonContains('audience_target', 'guardian')
              ->orWhereJsonContains('audience_target', 'all');
        });

        $totalCount = (clone $query)->count();

        $unreadCount = (clone $query)->whereDoesntHave('viewers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'unread_count' => $unreadCount,
                'total_count' => $totalCount,
            ]
        ]);
    }

    /**
     * Get weekly class timetable for a specific student linked to this guardian.
     */
    public function studentTimetable(Request $request, string $studentId): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json([
                'status' => 'error',
                'message' => 'Guardian record not found.',
            ], 404);
        }

        $student = $guardian->students()
            ->where('students.id', $studentId)
            ->with(['schoolClass:id,name,category', 'classArm:id,school_class_id,name'])
            ->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Student not found or not associated with this guardian.',
            ], 404);
        }

        if (!$student->class_arm_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Student is not assigned to any class arm yet.',
            ], 422);
        }

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $periods = \App\Models\TimetablePeriod::orderBy('order', 'asc')->get();

        $currentSession = \App\Models\AcademicSession::where('is_current', true)->first();
        $sessionId = $request->query('academic_session_id') ?? $currentSession?->id;
        $rawTerm = $request->query('term') ?? $currentSession?->current_term ?? 1;
        $term = (int) ($rawTerm instanceof \App\Enums\TermEnum ? $rawTerm->value : $rawTerm);

        $slots = \App\Models\TimetableSlot::where('class_arm_id', $student->class_arm_id)
            ->where('academic_session_id', $sessionId)
            ->where('term', $term)
            ->with([
                'teacher:id,name,email',
                'subject:id,name,code',
                'period:id,name,period_number,start_time,end_time,is_break,order',
            ])
            ->get();

        $slotsGrouped = [];
        foreach ($slots as $slot) {
            $key = $slot->day_of_week . '_' . $slot->timetable_period_id;
            $slotsGrouped[$key] = [
                'id' => $slot->id,
                'subject_id' => $slot->subject_id,
                'subject_name' => $slot->subject?->name ?? '',
                'teacher_id' => $slot->teacher_id,
                'teacher_name' => $slot->teacher?->name ?? '',
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
                'student' => [
                    'id' => $student->id,
                    'name' => trim("{$student->first_name} {$student->middle_name} {$student->last_name}"),
                    'class_name' => ($student->schoolClass?->name ?? '') . ' ' . ($student->classArm?->name ?? ''),
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

