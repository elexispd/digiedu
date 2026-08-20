<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'occupation',
        'email',
        'home_address',
        'lga',
        'state',
        'status',
        'notification_preferences'
    ];

    protected function casts(): array
    {
        return [
            'notification_preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withPivot('relationship')->withTimestamps();
    }
}
