<?php

namespace App\Services\Billing;

use App\Contracts\Listing;
use App\Enums\PaymentMethod;
use App\Enums\PricePlanProduct;
use App\Exceptions\PurchaseNotAllowed;
use App\Models\Order;
use App\Models\PricePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Creates pending orders with a fixed price taken from the plan at the moment of purchase.
 */
class OrderBuilder
{
    public function __construct(private Catalog $catalog) {}

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws PurchaseNotAllowed
     */
    public function forListing(User $user, PricePlan $plan, Model&Listing $listing, PaymentMethod $method, array $options = []): Order
    {
        if ((int) $listing->getAttribute('user_id') !== (int) $user->getKey() || ! $this->catalog->canPromote($listing) || ! $this->catalog->allowsForListing($plan, $listing)) {
            throw new PurchaseNotAllowed;
        }

        return Order::query()->create([
            'user_id' => $user->getKey(),
            'price_plan_id' => $plan->getKey(),
            'product' => $plan->product,
            'purchasable_type' => $listing->getMorphClass(),
            'purchasable_id' => $listing->getKey(),
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'method' => $method,
            'options' => $options === [] ? null : $options,
        ]);
    }

    /**
     * @throws PurchaseNotAllowed
     */
    public function forPackage(User $user, PricePlan $plan, PaymentMethod $method): Order
    {
        if (! $plan->is_active || $plan->product !== PricePlanProduct::BusinessPackage) {
            throw new PurchaseNotAllowed;
        }

        return Order::query()->create([
            'user_id' => $user->getKey(),
            'price_plan_id' => $plan->getKey(),
            'product' => $plan->product,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'method' => $method,
        ]);
    }

    /**
     * @throws PurchaseNotAllowed
     */
    public function topUp(User $user, float $amount): Order
    {
        $amount = round($amount, 2);

        if ($amount < (float) config('payments.top_up.min') || $amount > (float) config('payments.top_up.max')) {
            throw new PurchaseNotAllowed;
        }

        return Order::query()->create([
            'user_id' => $user->getKey(),
            'product' => PricePlanProduct::WalletTopUp,
            'amount' => $amount,
            'currency' => config('payments.currency'),
            'method' => PaymentMethod::Card,
        ]);
    }

    /**
     * A staff-issued invoice (banner slot, special deal) the user pays from the account.
     *
     * @param  array<string, string>  $description  locale => text
     */
    public function invoice(User $user, float $amount, array $description, User $staff, ?PricePlan $plan = null, ?Model $purchasable = null): Order
    {
        return Order::query()->create([
            'user_id' => $user->getKey(),
            'price_plan_id' => $plan?->getKey(),
            'product' => $plan->product ?? PricePlanProduct::Custom,
            'purchasable_type' => $purchasable?->getMorphClass(),
            'purchasable_id' => $purchasable?->getKey(),
            'description' => array_filter($description),
            'amount' => round($amount, 2),
            'currency' => config('payments.currency'),
            'issued_by' => $staff->getKey(),
        ]);
    }
}
