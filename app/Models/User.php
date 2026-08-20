<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'biometric_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'biometric_enabled' => 'boolean',
        ];
    }

    public function viewedAnnouncements(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_user_views', 'user_id', 'announcement_id')
            ->withPivot('viewed_at');
    }

    public function guardian(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    /**
     * Get the subjects assigned to this user (teacher).
     */
    public function subjects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_assignments', 'user_id', 'subject_id')
            ->withPivot(['id', 'school_class_id', 'academic_session_id'])
            ->withTimestamps();
    }

    public function subjectAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubjectAssignment::class, 'user_id');
    }

    public function conversationsAsOne(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Conversation::class, 'participant_one_id');
    }

    public function conversationsAsTwo(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Conversation::class, 'participant_two_id');
    }

    public function sentMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
