<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One browser's web push subscription (endpoint and keys from PushManager.subscribe()).
 */
#[Fillable(['user_id', 'endpoint', 'public_key', 'auth_token', 'content_encoding'])]
#[Hidden(['public_key', 'auth_token'])]
class PushSubscription extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
