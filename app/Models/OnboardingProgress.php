<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OnboardingProgress extends Model
{
    use HasUuids;

    protected $table = 'onboarding_progress';

    protected $fillable = [
        'current_step',
        'completed_steps',
        'is_completed'
    ];

    protected function casts(): array
    {
        return [
            'completed_steps' => 'array',
            'is_completed' => 'boolean'
        ];
    }
}
