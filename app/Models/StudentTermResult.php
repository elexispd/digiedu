<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTermResult extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'academic_session_id',
        'term',
        'school_class_id',
        'class_arm_id',
        'student_id',
        'total_score',
        'average_score',
        'class_arm_position',
        'overall_class_position',
        'teacher_remark',
        'principal_remark',
        'status',
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

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
