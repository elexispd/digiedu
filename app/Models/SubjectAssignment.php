<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubjectAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'subject_id',
        'school_class_id',
        'academic_session_id',
    ];

    public function teacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function academicSession(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }
}
