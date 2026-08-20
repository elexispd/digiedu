<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentResult extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'academic_session_id',
        'term',
        'school_class_id',
        'class_arm_id',
        'subject_id',
        'student_id',
        'total_score',
        'grade',
        'remark',
        'subject_position',
        'status',
        'recorded_by',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function classArm()
    {
        return $this->belongsTo(ClassArm::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function details()
    {
        return $this->hasMany(StudentResultDetail::class);
    }
}
