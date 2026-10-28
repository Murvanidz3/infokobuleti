<?php

namespace App\Models;

use App\Enums\SmsKind;
use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery log of every SMS. The text is stored only for messages written by staff (single
 * sends, campaigns); never for sign-in codes.
 */
#[Guarded(['id'])]
class SmsMessage extends Model
{
    public const string PURPOSE_SINGLE = 'admin.single';

    public const string PURPOSE_CAMPAIGN = 'campaign';

    public const string PURPOSE_TEST = 'campaign.test';

    protected function casts(): array
    {
        return [
            'status' => SmsStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SmsCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function kind(): SmsKind
    {
        return SmsKind::fromPurpose($this->purpose);
    }

    public function isOneTimeCode(): bool
    {
        return str_starts_with($this->purpose, 'otp.');
    }
}
