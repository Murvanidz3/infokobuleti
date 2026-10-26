<?php

namespace App\Actions\Billing;

use App\Enums\OrderStatus;
use App\Enums\PricePlanProduct;
use App\Enums\PromotionType;
use App\Enums\WalletEntryType;
use App\Models\Banner;
use App\Models\BusinessProfile;
use App\Models\BusinessSubscription;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\User;
use App\Notifications\PaymentReceipt;
use App\Services\Billing\Promotions;
use App\Services\Billing\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turns a paid order into what was bought: a promotion, a business package, wallet money or an
 * active banner. Runs once per order (the order row is locked and checked), then sends the receipt.
 */
class ActivatePurchase
{
    public function __construct(
        private Wallet $wallet,
        private Promotions $promotions,
    ) {}

    public function handle(Order $order): Order
    {
        $activated = DB::transaction(function () use ($order): bool {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::Paid || $locked->fulfilled_at !== null) {
                return false;
            }

            $locked->loadMissing(['user', 'pricePlan']);
            $this->activate($locked);

            $locked->forceFill(['status' => OrderStatus::Fulfilled, 'fulfilled_at' => now()])->save();
            $order->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if ($activated) {
            $order->loadMissing('user');
            $order->user->notify(new PaymentReceipt($order));
        }

        return $order;
    }

    private function activate(Order $order): void
    {
        $days = $order->pricePlan->duration_days ?? null;

        match ($order->product) {
            PricePlanProduct::WalletTopUp => $this->wallet->credit($order->user, (float) $order->amount, WalletEntryType::TopUp, $order),
            PricePlanProduct::BusinessPackage => $this->startPackage($order, $days ?? 30),
            PricePlanProduct::Banner => $this->activateBanner($order, $days ?? 30),
            PricePlanProduct::Custom => $order->purchasable_type === 'banner' ? $this->activateBanner($order, $days ?? 30) : null,
            default => $this->promote($order, $days),
        };
    }

    private function promote(Order $order, ?int $days): void
    {
        $type = $order->product->promotionType();
        $listing = $this->purchasable($order);

        if ($type === null || $listing === null) {
            return;
        }

        $promotion = new Promotion([
            'user_id' => $order->user_id,
            'order_id' => $order->getKey(),
            'type' => $type,
            'starts_at' => now(),
            'ends_at' => $type->hasDuration() ? $this->endsAfter($listing, $type, $days ?? 7) : null,
            'next_run_at' => $type === PromotionType::AutoBump ? now()->addHours((int) config('payments.auto_bump_every_hours')) : null,
        ]);
        $promotion->promotable()->associate($listing);
        $promotion->save();

        // An auto-bump starts with a bump right away.
        if ($type === PromotionType::AutoBump) {
            $this->promotions->bump($listing, $order->user_id, $order->getKey());
        }
    }

    /**
     * A second purchase of the same promotion extends it: the new one ends $days after the
     * current one would.
     */
    private function endsAfter(Model $listing, PromotionType $type, int $days): Carbon
    {
        $current = Promotion::query()
            ->where('promotable_type', $listing->getMorphClass())
            ->where('promotable_id', $listing->getKey())
            ->where('type', $type->value)
            ->active()
            ->max('ends_at');

        $from = $current !== null ? Carbon::parse($current)->max(now()) : now();

        return $from->copy()->addDays($days);
    }

    private function startPackage(Order $order, int $days): void
    {
        $current = BusinessSubscription::query()->where('user_id', $order->user_id)->active()->max('ends_at');
        $from = $current !== null ? Carbon::parse($current)->max(now()) : now();

        BusinessSubscription::query()->create([
            'user_id' => $order->user_id,
            'order_id' => $order->getKey(),
            'price_plan_id' => $order->price_plan_id,
            'listings_limit' => $order->pricePlan->listings_limit ?? null,
            'starts_at' => now(),
            'ends_at' => $from->copy()->addDays($days),
        ]);

        self::syncPackageFlag($order->user);
    }

    private function activateBanner(Order $order, int $days): void
    {
        $banner = $order->purchasable_type === 'banner' ? Banner::query()->find($order->purchasable_id) : null;

        if ($banner === null) {
            return;
        }

        $starts = $banner->starts_at !== null && $banner->starts_at->isFuture() ? $banner->starts_at : now();

        $banner->forceFill([
            'is_active' => true,
            'starts_at' => $starts,
            'ends_at' => $banner->ends_at !== null && $banner->ends_at->greaterThan($starts) ? $banner->ends_at : $starts->copy()->addDays($days),
        ])->save();
    }

    private function purchasable(Order $order): ?Model
    {
        if ($order->purchasable_type === null) {
            return null;
        }

        $class = Model::getActualClassNameForMorph($order->purchasable_type);

        /** @var Model|null */
        return $class::query()->withoutGlobalScopes()->find($order->purchasable_id);
    }

    /**
     * Copies the end of the user's running package onto their business profiles (verified badge,
     * logo on cards).
     */
    public static function syncPackageFlag(User $user): void
    {
        $until = BusinessSubscription::query()->where('user_id', $user->getKey())->active()->max('ends_at');

        BusinessProfile::query()->where('user_id', $user->getKey())->update(['package_until' => $until]);
    }
}
