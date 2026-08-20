<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'category',
        'urgency_level',
        'audience_target',
        'scheduled_date',
        'message_content',
        'total_views'
    ];

    protected function casts(): array
    {
        return [
            'audience_target' => 'array',
            'scheduled_date' => 'datetime',
        ];
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user_views', 'announcement_id', 'user_id')
            ->withPivot('viewed_at');
    }
}
