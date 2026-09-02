<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Student extends Model
{
    use HasUuids;

    protected $appends = ['attendance_percentage', 'average_score'];

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'dob',
        'admission_number',
        'photo',
        'blood_group',
        'genotype',
        'nationality',
        'religion',
        'academic_session_id',
        'term',
        'admission_date',
        'class_category',
        'school_class_id',
        'class_arm_id',
        'student_type',
        'house',
        'roll_number',
        'current_status'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)->withPivot('relationship')->withTimestamps();
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentAttendance::class, 'student_id');
    }

    public function conductLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConductLog::class, 'student_id');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ExtracurricularActivity::class, 'activity_student', 'student_id', 'extracurricular_activity_id')
            ->withPivot('enrolled_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'student_badge')
            ->withPivot('awarded_by', 'awarded_at')
            ->withTimestamps();
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class);
    }

    public function termResults(): HasMany
    {
        return $this->hasMany(StudentTermResult::class, 'student_id')
            ->orderBy('created_at', 'desc');
    }

    public function previousTermResult(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentTermResult::class, 'student_id')
            ->latestOfMany('created_at');
    }

    /**
     * Get dummy attendance percentage (placeholder until feature is built).
     */
    protected function attendancePercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => 85.5, // Dummy value
        );
    }

    /**
     * Get dummy average score/grade (placeholder until feature is built).
     */
    protected function averageScore(): Attribute
    {
        return Attribute::make(
            get: fn () => 49.9, // Dummy value
        );
    }
}
