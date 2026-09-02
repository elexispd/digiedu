<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetablePeriod extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'period_number',
        'start_time',
        'end_time',
        'is_break',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
            'is_break' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function slots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class, 'timetable_period_id');
    }
}
