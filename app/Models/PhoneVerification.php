<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One SMS code sent to a phone. Only an HMAC of the code is stored.
 */
#[Guarded(['id'])]
class PhoneVerification extends Model
{
    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Codes that can still be entered: not used, not replaced by a newer code.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query, string $phone, OtpPurpose $purpose): void
    {
        $query->where('phone', $phone)->where('purpose', $purpose->value)->whereNull('consumed_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
