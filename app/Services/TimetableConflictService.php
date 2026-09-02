<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\TimetablePeriod;
use App\Models\TimetableSlot;

class TimetableConflictService
{
    /**
     * Check for scheduling conflicts before setting or updating a timetable slot.
     *
     * @param array $data
     * @param string|null $excludeSlotId
     * @return array
     */
    public function checkConflict(array $data, ?string $excludeSlotId = null): array
    {
        $teacherId = $data['teacher_id'] ?? null;
        $classArmId = $data['class_arm_id'] ?? null;
        $timetablePeriodId = $data['timetable_period_id'] ?? null;
        $dayOfWeek = strtolower($data['day_of_week'] ?? '');
        $room = !empty($data['room']) ? trim($data['room']) : null;

        // Resolve academic session and term
        $academicSessionId = $data['academic_session_id'] ?? null;
        $term = $data['term'] ?? null;

        if (!$academicSessionId) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            $academicSessionId = $currentSession?->id;
            $currentTerm = $currentSession?->current_term instanceof \App\Enums\TermEnum
                ? $currentSession->current_term->value
                : $currentSession?->current_term;
            $term = $term ?? $currentTerm ?? 1;
        }

        $term = $term instanceof \App\Enums\TermEnum ? $term->value : (int) $term;

        // 1. Check if period is a designated break
        if ($timetablePeriodId) {
            $period = TimetablePeriod::find($timetablePeriodId);
            if ($period && $period->is_break) {
                return [
                    'has_conflict' => true,
                    'conflict' => [
                        'type' => 'break_conflict',
                        'message' => "Scheduling Conflict: {$period->name} is reserved for school break and cannot be assigned for classes.",
                        'conflicting_slot' => null,
                    ],
                ];
            }
        }

        // Base query helper
        $baseQuery = function () use ($academicSessionId, $term, $dayOfWeek, $timetablePeriodId, $excludeSlotId) {
            $query = TimetableSlot::query()
                ->where('day_of_week', $dayOfWeek)
                ->where('timetable_period_id', $timetablePeriodId);

            if ($academicSessionId) {
                $query->where(function ($q) use ($academicSessionId) {
                    $q->where('academic_session_id', $academicSessionId)
                      ->orWhereNull('academic_session_id');
                });
            }
            if ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('term', $term)
                      ->orWhereNull('term');
                });
            }
            if ($excludeSlotId) {
                $query->where('id', '!=', $excludeSlotId);
            }

            return $query;
        };

        // 2. Class Conflict: Class arm already has a class in this period and day
        if ($classArmId && $timetablePeriodId && $dayOfWeek) {
            $classConflict = $baseQuery()
                ->where('class_arm_id', $classArmId)
                ->with([
                    'classArm:id,school_class_id,name',
                    'classArm.schoolClass:id,name',
                    'subject:id,name',
                    'teacher:id,name',
                    'period:id,name,period_number',
                ])
                ->first();

            if ($classConflict) {
                $className = ($classConflict->classArm?->schoolClass?->name ?? 'Class') . ' ' . ($classConflict->classArm?->name ?? '');
                $subjectName = $classConflict->subject?->name ?? 'another subject';
                $teacherName = $classConflict->teacher?->name ?? 'another teacher';
                $periodName = $classConflict->period?->period_number ? 'P' . $classConflict->period->period_number : ($classConflict->period?->name ?? 'this period');
                $dayName = ucfirst($dayOfWeek);

                return [
                    'has_conflict' => true,
                    'conflict' => [
                        'type' => 'class_conflict',
                        'message' => "Class Conflict: {$className} already has a scheduled class \"{$subjectName}\" with {$teacherName} on {$dayName} during {$periodName}.",
                        'conflicting_slot' => $classConflict,
                    ],
                ];
            }
        }

        // 3. Teacher Conflict: Teacher is already teaching another class in this period and day
        if ($teacherId && $timetablePeriodId && $dayOfWeek) {
            $teacherConflict = $baseQuery()
                ->where('teacher_id', $teacherId)
                ->with([
                    'classArm:id,school_class_id,name',
                    'classArm.schoolClass:id,name',
                    'subject:id,name',
                    'teacher:id,name',
                    'period:id,name,period_number',
                ])
                ->first();

            if ($teacherConflict) {
                $className = ($teacherConflict->classArm?->schoolClass?->name ?? 'Class') . ' ' . ($teacherConflict->classArm?->name ?? '');
                $subjectName = $teacherConflict->subject?->name ?? 'a subject';
                $teacherName = $teacherConflict->teacher?->name ?? 'Selected teacher';
                $periodName = $teacherConflict->period?->period_number ? 'P' . $teacherConflict->period->period_number : ($teacherConflict->period?->name ?? 'this period');
                $dayName = ucfirst($dayOfWeek);

                return [
                    'has_conflict' => true,
                    'conflict' => [
                        'type' => 'teacher_conflict',
                        'message' => "Teacher Conflict: {$teacherName} is already assigned to {$className} for \"{$subjectName}\" on {$dayName} during {$periodName}.",
                        'conflicting_slot' => $teacherConflict,
                    ],
                ];
            }
        }

        // 4. Room Conflict: Room is already occupied in this period and day
        if ($room && $timetablePeriodId && $dayOfWeek) {
            $roomConflict = $baseQuery()
                ->where('room', $room)
                ->with([
                    'classArm:id,school_class_id,name',
                    'classArm.schoolClass:id,name',
                    'teacher:id,name',
                    'period:id,name,period_number',
                ])
                ->first();

            if ($roomConflict) {
                $className = ($roomConflict->classArm?->schoolClass?->name ?? 'Class') . ' ' . ($roomConflict->classArm?->name ?? '');
                $periodName = $roomConflict->period?->period_number ? 'P' . $roomConflict->period->period_number : ($roomConflict->period?->name ?? 'this period');
                $dayName = ucfirst($dayOfWeek);

                return [
                    'has_conflict' => true,
                    'conflict' => [
                        'type' => 'room_conflict',
                        'message' => "Room Conflict: Room \"{$room}\" is already occupied by {$className} on {$dayName} during {$periodName}.",
                        'conflicting_slot' => $roomConflict,
                    ],
                ];
            }
        }

        return [
            'has_conflict' => false,
            'conflict' => null,
        ];
    }
}
