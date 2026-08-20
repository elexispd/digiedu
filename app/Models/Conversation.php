<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'participant_one_id',
        'participant_two_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function participantOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_one_id');
    }

    public function participantTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Scope: conversations where the given user is either participant.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('participant_one_id', $userId)
              ->orWhere('participant_two_id', $userId);
        });
    }

    /**
     * Get the other participant relative to a given user.
     */
    public function otherParticipant(string $userId): ?User
    {
        if ($this->participant_one_id === $userId) {
            return $this->participantTwo;
        }
        return $this->participantOne;
    }

    /**
     * Count of unread messages for a given user in this conversation.
     */
    public function unreadCountFor(string $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
