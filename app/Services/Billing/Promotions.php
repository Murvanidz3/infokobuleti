<?php

namespace App\Services\Billing;

use App\Actions\Billing\ActivatePurchase;
use App\Enums\PromotionType;
use App\Models\BusinessSubscription;
use App\Models\Promotion;
use App\Models\User;
use App\Notifications\PromotionExpiring;
use App\Services\Account\OwnerListings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The scheduled side of the promotion engine: automatic bumps, expiry of the cached promotion
 * columns, and "ends tomorrow" reminders for promotions and business packages.
 */
class Promotions
{
    public function bump(Model $listing, int $userId, ?int $orderId = null): Promotion
    {
        $bump = new Promotion([
            'user_id' => $userId,
            'order_id' => $orderId,
            'type' => PromotionType::Bump,
            'starts_at' => now(),
        ]);
        $bump->promotable()->associate($listing);
        $bump->save();

        return $bump;
    }

    /**
     * Auto-bumps whose next run is due. Returns how many listings were bumped.
     */
    public function runAutoBumps(): int
    {
        $count = 0;
        $every = (int) config('payments.auto_bump_every_hours');

        Promotion::query()
            ->where('type', PromotionType::AutoBump->value)
            ->active()
            ->where('next_run_at', '<=', now())
            ->with('promotable')
            ->chunkById(100, function ($promotions) use (&$count, $every): void {
                foreach ($promotions as $promotion) {
                    DB::transaction(function () use ($promotion, &$count, $every): void {
                        $locked = Promotion::query()->whereKey($promotion->getKey())->lockForUpdate()->first();

                        if ($locked === null || $locked->next_run_at === null || $locked->next_run_at->isFuture() || $promotion->promotable === null) {
                            return;
                        }

                        // Only live listings move up; the schedule continues either way.
                        if (method_exists($promotion->promotable, 'isPublished') && $promotion->promotable->isPublished()) {
                            $this->bump($promotion->promotable, (int) $locked->user_id, $locked->order_id);
                            $count++;
                        }

                        $next = $locked->next_run_at->copy();

                        while ($next->lessThanOrEqualTo(now())) {
                            $next->addHours($every);
                        }

                        $locked->forceFill(['next_run_at' => $next])->saveQuietly();
                    });
                }
            });

        return $count;
    }

    /**
     * Listings whose cached promotion level is out of date (a VIP ended, or a queued one started).
     * Returns how many listings were refreshed.
     */
    public function refreshExpired(): int
    {
        $count = 0;

        foreach (OwnerListings::TYPES as $class) {
            $class::query()
                ->withoutGlobalScopes()
                ->where('promotion_level', '>', 0)
                ->where(fn ($query) => $query->whereNull('promoted_until')->orWhere('promoted_until', '<=', now()))
                ->chunkById(100, function ($listings) use (&$count): void {
                    foreach ($listings as $listing) {
                        $listing->refreshPromotionCache();
                        $count++;
                    }
                });
        }

        // Packages that ended: drop the badge and logo flag.
        BusinessSubscription::query()
            ->whereNull('cancelled_at')
            ->whereBetween('ends_at', [now()->subDays(2), now()])
            ->pluck('user_id')
            ->unique()
            ->each(function (int $userId): void {
                $user = User::query()->find($userId);

                if ($user !== null) {
                    ActivatePurchase::syncPackageFlag($user);
                }
            });

        return $count;
    }

    /**
     * Reminds owners one day before a VIP, Super VIP, homepage slot, auto-bump or business package
     * ends. Each is reminded once.
     */
    public function notifyExpiring(): int
    {
        $count = 0;
        $window = [now(), now()->addDay()];

        Promotion::query()
            ->whereIn('type', [PromotionType::Vip->value, PromotionType::SuperVip->value, PromotionType::FeaturedHome->value, PromotionType::AutoBump->value])
            ->active()
            ->whereNull('expiry_notified_at')
            ->whereBetween('ends_at', $window)
            ->with(['user', 'promotable'])
            ->chunkById(100, function ($promotions) use (&$count): void {
                foreach ($promotions as $promotion) {
                    $promotion->forceFill(['expiry_notified_at' => now()])->saveQuietly();

                    // A newer purchase of the same promotion already extends it.
                    $extended = Promotion::query()
                        ->where('promotable_type', $promotion->promotable_type)
                        ->where('promotable_id', $promotion->promotable_id)
                        ->where('type', $promotion->type->value)
                        ->active()
                        ->where('ends_at', '>', $promotion->ends_at)
                        ->exists();

                    if (! $extended && $promotion->user !== null && $promotion->promotable !== null) {
                        $promotion->user->notify(new PromotionExpiring($promotion->type->value, $promotion->promotable, $promotion->ends_at));
                        $count++;
                    }
                }
            });

        BusinessSubscription::query()
            ->active()
            ->whereNull('expiry_notified_at')
            ->whereBetween('ends_at', $window)
            ->with('user')
            ->chunkById(100, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $subscription->forceFill(['expiry_notified_at' => now()])->saveQuietly();

                    $extended = BusinessSubscription::query()->where('user_id', $subscription->user_id)->active()->where('ends_at', '>', $subscription->ends_at)->exists();

                    if (! $extended && $subscription->user !== null) {
                        $subscription->user->notify(new PromotionExpiring('business_package', null, $subscription->ends_at));
                        $count++;
                    }
                }
            });

        return $count;
    }
}
