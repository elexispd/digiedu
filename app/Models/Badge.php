<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = [
        'name',
        'type',
        'category',
        'description',
        'logo',
        'level',
    ];

    /**
     * The students that have been awarded this badge.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_badge')
            ->withPivot('awarded_by', 'awarded_at')
            ->withTimestamps();
    }
}
