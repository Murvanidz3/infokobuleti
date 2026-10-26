<?php

namespace App\Models;

use App\Enums\PaymentEventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The payment log: each request to a bank and each message from it, as received.
 */
#[Fillable(['payment_id', 'gateway', 'type', 'status', 'signature_valid', 'payload', 'ip'])]
class PaymentEvent extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => PaymentEventType::class,
            'signature_valid' => 'boolean',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
