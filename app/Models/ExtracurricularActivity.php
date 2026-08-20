<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExtracurricularActivity extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'category',
        'coordinator_id',
        'schedule_days',
        'venue'
    ];

    protected function casts(): array
    {
        return [
            'schedule_days' => 'array',
        ];
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'activity_student', 'extracurricular_activity_id', 'student_id')
            ->withPivot('enrolled_at');
    }
}
