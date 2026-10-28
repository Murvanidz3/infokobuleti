<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * How the owner's account groups listings: by what the owner can do next, not by raw status.
 */
enum ListingGroup: string
{
    use HasLabel;

    case Active = 'active';
    case Pending = 'pending';
    case Attention = 'attention';
    case Paused = 'paused';
    case Expired = 'expired';

    /**
     * Group of a listing. Published items past their expiry date count as expired even before
     * the nightly listings:expire run changes their status.
     */
    public static function of(ListingStatus $status, mixed $expiresAt): ?self
    {
        return match ($status) {
            ListingStatus::Published => $expiresAt !== null && now()->greaterThanOrEqualTo($expiresAt) ? self::Expired : self::Active,
            ListingStatus::Pending => self::Pending,
            ListingStatus::Draft, ListingStatus::Rejected => self::Attention,
            ListingStatus::Paused => self::Paused,
            ListingStatus::Expired => self::Expired,
            ListingStatus::Archived => null,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'check-circle',
            self::Pending => 'clock',
            self::Attention => 'alert',
            self::Paused => 'minus',
            self::Expired => 'calendar',
        };
    }
}
