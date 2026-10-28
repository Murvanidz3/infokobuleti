<?php

namespace App\Services\Listings;

use App\Enums\AccountType;
use App\Enums\ListingStatus;
use App\Models\User;
use App\Services\Account\OwnerListings;
use App\Services\Settings;

/**
 * Free listings per calendar month by account type (admin setting "listings.free_limit.*").
 * No value means no limit. Deleted listings still count, so delete-and-repost cannot bypass it.
 * A business package replaces the monthly limit with its number of active listings.
 */
class ListingLimits
{
    public function __construct(private Settings $settings) {}

    public function monthlyLimit(User $user): ?int
    {
        $type = $user->account_type ?? AccountType::Individual;
        $limit = $this->settings->get('listings.free_limit.'.$type->value);

        return is_numeric($limit) ? max(0, (int) $limit) : null;
    }

    /**
     * Active listings the user's business package allows, or null without a package (or with an
     * unlimited one).
     */
    public function packageLimit(User $user): ?int
    {
        $limit = $user->activeBusinessSubscription()?->listings_limit;

        return $limit !== null ? max(0, (int) $limit) : null;
    }

    /**
     * Listings that count against a package: live, waiting for moderation or paused.
     */
    public function activeCount(User $user): int
    {
        $count = 0;

        foreach (OwnerListings::TYPES as $class) {
            $count += $class::query()
                ->where('user_id', $user->getKey())
                ->whereIn('status', [ListingStatus::Published->value, ListingStatus::Pending->value, ListingStatus::Paused->value])
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count();
        }

        return $count;
    }

    public function limitFor(User $user): ?int
    {
        return $this->packageLimit($user) ?? $this->monthlyLimit($user);
    }

    public function usedThisMonth(User $user): int
    {
        $used = 0;

        foreach (OwnerListings::TYPES as $class) {
            $used += $class::withTrashed()
                ->where('user_id', $user->getKey())
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();
        }

        return $used;
    }

    /**
     * Free listings left this month, or null when unlimited.
     */
    public function remaining(User $user): ?int
    {
        if ($user->isStaff()) {
            return null;
        }

        if ($user->activeBusinessSubscription() !== null) {
            $package = $this->packageLimit($user);

            return $package === null ? null : max(0, $package - $this->activeCount($user));
        }

        $limit = $this->monthlyLimit($user);

        return $limit === null ? null : max(0, $limit - $this->usedThisMonth($user));
    }

    public function canCreate(User $user): bool
    {
        $remaining = $this->remaining($user);

        return $remaining === null || $remaining > 0;
    }
}
