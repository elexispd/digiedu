<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimetableSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => 'sometimes|required|uuid|exists:users,id',
            'class_arm_id' => 'sometimes|required|uuid|exists:class_arms,id',
            'subject_id' => 'sometimes|required|uuid|exists:subjects,id',
            'timetable_period_id' => 'sometimes|required|uuid|exists:timetable_periods,id',
            'day_of_week' => 'sometimes|required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'session_type' => 'sometimes|string|in:lecture,practical,tutorial,revision',
            'room' => 'nullable|string|max:100',
            'academic_session_id' => 'nullable|uuid|exists:academic_sessions,id',
            'term' => 'nullable|integer|between:1,3',
        ];
    }
}
