<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableSlot extends Model
{
    use HasUuids;

    protected $fillable = [
        'academic_session_id',
        'term',
        'teacher_id',
        'school_class_id',
        'class_arm_id',
        'subject_id',
        'timetable_period_id',
        'day_of_week',
        'session_type',
        'room',
    ];

    protected function casts(): array
    {
        return [
            'term' => 'integer',
        ];
    }

    public function setTermAttribute($value): void
    {
        if ($value instanceof \App\Enums\TermEnum) {
            $this->attributes['term'] = $value->value;
        } elseif ($value !== null) {
            $this->attributes['term'] = (int) $value;
        } else {
            $this->attributes['term'] = null;
        }
    }

    protected static function booted(): void
    {
        static::creating(function ($slot) {
            if (empty($slot->academic_session_id)) {
                $currentSession = AcademicSession::where('is_current', true)->first();
                $slot->academic_session_id = $currentSession?->id;
                $currentTerm = $currentSession?->current_term instanceof \App\Enums\TermEnum
                    ? $currentSession->current_term->value
                    : $currentSession?->current_term;
                $slot->term = $slot->term ?? $currentTerm ?? 1;
            }
        });
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class, 'class_arm_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TimetablePeriod::class, 'timetable_period_id');
    }
}
