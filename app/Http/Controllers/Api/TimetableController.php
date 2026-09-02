<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TimetablePeriod;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\TimetableConflictService;
use App\Http\Requests\CheckTimetableConflictRequest;
use App\Http\Requests\StoreTimetableSlotRequest;
use App\Http\Requests\UpdateTimetableSlotRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TimetableController extends Controller
{
    public function __construct(
        protected TimetableConflictService $conflictService
    ) {}

    /**
     * View 1: Timetable by Teacher (Image 1).
     */
    public function byTeacher(Request $request): JsonResponse
    {
        Gate::authorize('view-timetable');

        $session = $this->resolveSession($request);
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // 1. Fetch periods
        $periods = TimetablePeriod::orderBy('order', 'asc')->get();

        // 2. Fetch list of teachers with their basic info and assigned subject names for the filter dropdown
        $teachersQuery = User::role('teacher')
            ->select('id', 'name', 'email')
            ->with([
                'subjectAssignments.subject:id,name,code',
            ]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $teachersQuery->where('name', 'LIKE', "%{$search}%");
        }

        $allTeachers = $teachersQuery->orderBy('name')->get();

        // 3. Resolve selected teacher
        $selectedTeacherId = $request->query('teacher_id') ?? $allTeachers->first()?->id;
        $selectedTeacher = $selectedTeacherId ? User::role('teacher')->find($selectedTeacherId) : null;

        if (!$selectedTeacher) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'selected_teacher' => null,
                    'periods' => $periods,
                    'grid' => [],
                    'staff_list' => $allTeachers,
                ],
            ]);
        }

        // 4. Fetch slots for this teacher in the active session/term
        $slots = TimetableSlot::where('teacher_id', $selectedTeacher->id)
            ->where('academic_session_id', $session['academic_session_id'])
            ->where('term', $session['term'])
            ->with([
                'schoolClass:id,name,category',
                'classArm:id,school_class_id,name',
                'subject:id,name,code',
                'period:id,name,period_number,start_time,end_time,is_break,order',
            ])
            ->get();

        // 5. Calculate workload and assigned subjects list
        $teacherSubjects = $selectedTeacher->subjectAssignments()
            ->with('subject:id,name')
            ->get()
            ->pluck('subject.name')
            ->unique()
            ->filter()
            ->values()
            ->all();

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

        // 6. Build the grid rows (one per period)
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
                'selected_teacher' => [
                    'id' => $selectedTeacher->id,
                    'name' => $selectedTeacher->name,
                    'staff_id' => 'STF/' . substr(preg_replace('/[^0-9]/', '', $selectedTeacher->id), 0, 4) . '/' . substr($selectedTeacher->id, 0, 3),
                    'subjects' => !empty($teacherSubjects) ? implode(', ', $teacherSubjects) : 'General',
                    'total_workload' => $slots->count(),
                ],
                'session' => $session,
                'periods' => $periods,
                'grid' => $grid,
                'staff_list' => $allTeachers->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'email' => $t->email,
                ]),
            ],
        ]);
    }

    /**
     * View 2: Timetable by Class (Image 2).
     */
    public function byClass(Request $request): JsonResponse
    {
        Gate::authorize('view-timetable');

        $session = $this->resolveSession($request);
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // 1. Fetch periods
        $periods = TimetablePeriod::orderBy('order', 'asc')->get();

        // 2. Fetch list of all class arms for the class dropdown
        $classArms = ClassArm::with('schoolClass:id,name,category')
            ->orderBy('name')
            ->get();

        // 3. Resolve selected class arm
        $selectedClassArmId = $request->query('class_arm_id') ?? $classArms->first()?->id;
        $selectedArm = $selectedClassArmId
            ? ClassArm::with('schoolClass:id,name,category')->find($selectedClassArmId)
            : null;

        if (!$selectedArm) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'selected_class' => null,
                    'periods' => $periods,
                    'grid' => [],
                    'class_list' => $classArms,
                ],
            ]);
        }

        // Count students in this class arm
        $studentCount = Student::where('class_arm_id', $selectedArm->id)->count();

        // 4. Fetch slots for this class arm
        $slots = TimetableSlot::where('class_arm_id', $selectedArm->id)
            ->where('academic_session_id', $session['academic_session_id'])
            ->where('term', $session['term'])
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

        // 5. Build grid rows
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
                'selected_class' => [
                    'id' => $selectedArm->id,
                    'school_class_id' => $selectedArm->school_class_id,
                    'name' => ($selectedArm->schoolClass?->name ?? 'Class') . ' ' . $selectedArm->name,
                    'full_title' => ($selectedArm->schoolClass?->name ?? 'Class') . ' ' . $selectedArm->name,
                    'student_count' => $studentCount,
                ],
                'session' => $session,
                'periods' => $periods,
                'grid' => $grid,
                'class_list' => $classArms->map(fn($arm) => [
                    'id' => $arm->id,
                    'school_class_id' => $arm->school_class_id,
                    'class_name' => $arm->schoolClass?->name ?? '',
                    'arm_name' => $arm->name,
                    'display_name' => ($arm->schoolClass?->name ?? '') . ' ' . $arm->name,
                ]),
            ],
        ]);
    }

    /**
     * View 3: Master Staff Allocation Grid (Image 3).
     */
    public function masterGrid(Request $request): JsonResponse
    {
        Gate::authorize('view-timetable');

        $session = $this->resolveSession($request);
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $teachersQuery = User::role('teacher')
            ->select('id', 'name', 'email');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $teachersQuery->where('name', 'LIKE', "%{$search}%");
        }

        $teachers = $teachersQuery->orderBy('name')->get();

        // Eager load all slots for all teachers in this session & term
        $allSlots = TimetableSlot::where('academic_session_id', $session['academic_session_id'])
            ->where('term', $session['term'])
            ->with([
                'classArm:id,school_class_id,name',
                'classArm.schoolClass:id,name',
                'subject:id,name,code',
                'period:id,name,period_number',
            ])
            ->get()
            ->groupBy('teacher_id');

        $matrix = [];
        foreach ($teachers as $teacher) {
            $teacherSlots = $allSlots->get($teacher->id, collect());

            $daysAllocation = [];
            foreach ($days as $day) {
                $daySlots = $teacherSlots->where('day_of_week', $day);

                $formattedSlots = [];
                foreach ($daySlots as $slot) {
                    $periodNum = $slot->period?->period_number ? 'P' . $slot->period->period_number : ($slot->period?->name ?? 'P');
                    $className = ($slot->classArm?->schoolClass?->name ?? '') . ($slot->classArm?->name ?? '');
                    $subjectName = $slot->subject?->name ?? '';

                    $formattedSlots[] = [
                        'slot_id' => $slot->id,
                        'period' => $periodNum,
                        'class' => $className,
                        'subject' => $subjectName,
                        'display' => "{$periodNum} • {$className} ({$subjectName})",
                    ];
                }

                $daysAllocation[$day] = $formattedSlots;
            }

            $matrix[] = [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'teacher_email' => $teacher->email,
                'allocations' => $daysAllocation,
                'total_load' => $teacherSlots->count(),
                'total_load_label' => $teacherSlots->count() . ' Periods',
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'session' => $session,
                'teachers' => $matrix,
            ],
        ]);
    }

    /**
     * View 4: Real-time Conflict Check for Modal (Image 4).
     */
    public function checkConflict(CheckTimetableConflictRequest $request): JsonResponse
    {
        Gate::authorize('view-timetable');

        $validated = $request->validated();
        $excludeSlotId = $validated['exclude_slot_id'] ?? null;

        $result = $this->conflictService->checkConflict($validated, $excludeSlotId);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * Store a newly assigned timetable slot.
     */
    public function store(StoreTimetableSlotRequest $request): JsonResponse
    {
        Gate::authorize('manage-timetable');

        $validated = $request->validated();

        // 1. Conflict Check
        $conflictResult = $this->conflictService->checkConflict($validated);
        if ($conflictResult['has_conflict']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Scheduling conflict detected',
                'conflict' => $conflictResult['conflict'],
            ], 422);
        }

        // 2. Resolve school_class_id from class_arm_id
        $classArm = ClassArm::findOrFail($validated['class_arm_id']);
        $validated['school_class_id'] = $classArm->school_class_id;

        // 3. Resolve session and term if omitted
        if (empty($validated['academic_session_id'])) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            $validated['academic_session_id'] = $currentSession?->id;
            $currentTerm = $currentSession?->current_term instanceof \App\Enums\TermEnum
                ? $currentSession->current_term->value
                : $currentSession?->current_term;
            $validated['term'] = $validated['term'] ?? $currentTerm ?? 1;
        }

        if (isset($validated['term']) && $validated['term'] instanceof \App\Enums\TermEnum) {
            $validated['term'] = $validated['term']->value;
        }
        $validated['term'] = (int) $validated['term'];

        $slot = TimetableSlot::create($validated);

        $slot->load([
            'teacher:id,name,email',
            'schoolClass:id,name,category',
            'classArm:id,school_class_id,name',
            'subject:id,name,code',
            'period:id,name,period_number,start_time,end_time,is_break,order',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Timetable slot assigned successfully',
            'data' => $slot,
        ], 201);
    }

    /**
     * Display a single timetable slot.
     */
    public function show(string $id): JsonResponse
    {
        Gate::authorize('view-timetable');

        $slot = TimetableSlot::with([
            'teacher:id,name,email',
            'schoolClass:id,name,category',
            'classArm:id,school_class_id,name',
            'subject:id,name,code',
            'period:id,name,period_number,start_time,end_time,is_break,order',
        ])->find($id);

        if (!$slot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timetable slot not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $slot,
        ]);
    }

    /**
     * Update an existing timetable slot.
     */
    public function update(UpdateTimetableSlotRequest $request, string $id): JsonResponse
    {
        Gate::authorize('manage-timetable');

        $slot = TimetableSlot::find($id);

        if (!$slot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timetable slot not found',
            ], 404);
        }

        $validated = $request->validated();
        $mergedData = array_merge($slot->toArray(), $validated);

        // Check conflicts excluding the current slot
        $conflictResult = $this->conflictService->checkConflict($mergedData, $id);
        if ($conflictResult['has_conflict']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Scheduling conflict detected',
                'conflict' => $conflictResult['conflict'],
            ], 422);
        }

        if (isset($validated['class_arm_id'])) {
            $classArm = ClassArm::find($validated['class_arm_id']);
            if ($classArm) {
                $validated['school_class_id'] = $classArm->school_class_id;
            }
        }

        $slot->update($validated);

        $slot->load([
            'teacher:id,name,email',
            'schoolClass:id,name,category',
            'classArm:id,school_class_id,name',
            'subject:id,name,code',
            'period:id,name,period_number,start_time,end_time,is_break,order',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Timetable slot updated successfully',
            'data' => $slot,
        ]);
    }

    /**
     * Delete a timetable slot.
     */
    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('manage-timetable');

        $slot = TimetableSlot::find($id);

        if (!$slot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timetable slot not found',
            ], 404);
        }

        $slot->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Timetable slot deleted successfully',
        ]);
    }

    /**
     * Helper to resolve active academic session and term.
     */
    private function resolveSession(Request $request): array
    {
        $sessionId = $request->query('academic_session_id');
        $term = $request->query('term');

        if (!$sessionId) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            $sessionId = $currentSession?->id;
            $currentTerm = $currentSession?->current_term instanceof \App\Enums\TermEnum
                ? $currentSession->current_term->value
                : $currentSession?->current_term;
            $term = $term ?? $currentTerm ?? 1;
        }

        $termVal = $term instanceof \App\Enums\TermEnum ? $term->value : (int) $term;

        return [
            'academic_session_id' => $sessionId,
            'term' => $termVal,
        ];
    }
}
