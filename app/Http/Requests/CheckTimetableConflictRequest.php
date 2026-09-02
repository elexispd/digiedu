<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckTimetableConflictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => 'required|uuid|exists:users,id',
            'class_arm_id' => 'required|uuid|exists:class_arms,id',
            'timetable_period_id' => 'required|uuid|exists:timetable_periods,id',
            'day_of_week' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'subject_id' => 'nullable|uuid|exists:subjects,id',
            'room' => 'nullable|string|max:100',
            'session_type' => 'nullable|string|in:lecture,practical,tutorial,revision',
            'exclude_slot_id' => 'nullable|uuid|exists:timetable_slots,id',
            'academic_session_id' => 'nullable|uuid|exists:academic_sessions,id',
            'term' => 'nullable|integer|between:1,3',
        ];
    }
}
