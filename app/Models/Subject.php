<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Subject extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
        'description',
        'department',
        'weekly_class_hours'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get the teachers assigned to this subject.
     */
    public function teachers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_assignments', 'subject_id', 'user_id')
            ->withPivot(['id', 'school_class_id', 'academic_session_id'])
            ->withTimestamps();
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }
}
