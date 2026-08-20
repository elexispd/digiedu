<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StudentAttendance extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'academic_session_id',
        'term',
        'date',
        'school_class_id',
        'class_arm_id',
        'status',
        'remark'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
