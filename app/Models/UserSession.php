<?php

namespace App\Models;

use App\Support\UserAgent;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A browser or device the user is signed in on. Works with every session driver: the row points to
 * the driver's session id, which is destroyed when the device is signed out remotely.
 */
#[Guarded(['id'])]
class UserSession extends Model
{
    protected $hidden = ['session_id'];

    protected function casts(): array
    {
        return [
            'remembered' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function agent(): UserAgent
    {
        return UserAgent::parse($this->user_agent);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
