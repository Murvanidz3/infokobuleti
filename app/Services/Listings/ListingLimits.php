<?php

namespace App\Services\Listings;

use App\Enums\AccountType;
use App\Models\User;
use App\Services\Account\OwnerListings;
use App\Services\Settings;

/**
 * Free listings per calendar month by account type (admin setting "listings.free_limit.*").
 * No value means no limit. Deleted listings still count, so delete-and-repost cannot bypass it.
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
        $limit = $this->monthlyLimit($user);

        if ($limit === null || $user->isStaff()) {
            return null;
        }

        return max(0, $limit - $this->usedThisMonth($user));
    }

    public function canCreate(User $user): bool
    {
        $remaining = $this->remaining($user);

        return $remaining === null || $remaining > 0;
    }
}
