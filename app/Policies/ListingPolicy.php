<?php

namespace App\Policies;

use App\Contracts\Listing;
use App\Enums\ListingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * What an owner may do with their own listing from the account area. Staff moderate in the admin
 * (Filament), which has its own permission checks.
 */
class ListingPolicy
{
    /** Renewal opens this many days before the expiry date. */
    public const int RENEW_WINDOW_DAYS = 7;

    public function update(User $user, Model&Listing $listing): bool
    {
        return $this->owns($user, $listing) && $listing->listingStatus() !== ListingStatus::Archived;
    }

    public function pause(User $user, Model&Listing $listing): bool
    {
        return $this->owns($user, $listing) && $listing->listingStatus() === ListingStatus::Published && ! $this->isPastExpiry($listing);
    }

    public function resume(User $user, Model&Listing $listing): bool
    {
        return $this->owns($user, $listing) && $listing->listingStatus() === ListingStatus::Paused && ! $this->isPastExpiry($listing);
    }

    public function renew(User $user, Model&Listing $listing): bool
    {
        if (! $this->owns($user, $listing) || $listing->expiryDays() === null || $listing->getAttribute('published_at') === null) {
            return false;
        }

        $expiresAt = $listing->getAttribute('expires_at');

        return match ($listing->listingStatus()) {
            ListingStatus::Expired => true,
            ListingStatus::Published, ListingStatus::Paused => $expiresAt !== null && now()->addDays(self::RENEW_WINDOW_DAYS)->greaterThanOrEqualTo($expiresAt),
            default => false,
        };
    }

    public function delete(User $user, Model&Listing $listing): bool
    {
        return $this->owns($user, $listing);
    }

    private function owns(User $user, Model $listing): bool
    {
        return ! $user->is_blocked && (int) $listing->getAttribute('user_id') === (int) $user->getKey();
    }

    private function isPastExpiry(Model $listing): bool
    {
        $expiresAt = $listing->getAttribute('expires_at');

        return $expiresAt !== null && now()->greaterThanOrEqualTo($expiresAt);
    }
}
