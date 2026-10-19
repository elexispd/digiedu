<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

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

    protected $appends = [
        'is_read',
        'isRead',
    ];

    protected function casts(): array
    {
        return [
            'audience_target' => 'array',
            'scheduled_date' => 'datetime',
            'is_read' => 'boolean',
            'isRead' => 'boolean',
        ];
    }

    public function getIsReadAttribute(): bool
    {
        if (array_key_exists('isRead', $this->attributes)) {
            return (bool) $this->attributes['isRead'];
        }
        if (array_key_exists('is_read', $this->attributes)) {
            return (bool) $this->attributes['is_read'];
        }

        $userId = Auth::id();
        if (!$userId) {
            return false;
        }

        if ($this->relationLoaded('viewers')) {
            return $this->viewers->contains('id', $userId);
        }

        return $this->viewers()->where('user_id', $userId)->exists();
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user_views', 'announcement_id', 'user_id')
            ->withPivot('viewed_at');
    }
}
