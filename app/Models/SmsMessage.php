<?php

namespace App\Models;

use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

/**
 * Delivery log of every SMS. The text itself is never stored (it may contain a login code).
 */
#[Guarded(['id'])]
class SmsMessage extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SmsStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
