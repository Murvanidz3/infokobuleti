<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PricePlanProduct;
use App\Models\Order;
use App\Models\PricePlan;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product' => PricePlanProduct::WalletTopUp,
            'amount' => 20,
            'currency' => Currency::GEL,
            'method' => PaymentMethod::Card,
        ];
    }

    /**
     * A VIP-type order for one of the user's properties.
     */
    public function forPlan(PricePlan $plan, ?Property $listing = null): static
    {
        return $this->state(fn (array $attributes) => [
            'price_plan_id' => $plan->id,
            'product' => $plan->product,
            'amount' => $plan->price,
            'purchasable_type' => $plan->product->isListingPromotion() ? 'property' : null,
            'purchasable_id' => $plan->product->isListingPromotion() ? ($listing !== null ? $listing->id : Property::factory()->published()->state(['user_id' => $attributes['user_id']])) : null,
        ]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'paid_at' => $status->isSettled() ? now() : null,
            'fulfilled_at' => $status === OrderStatus::Fulfilled ? now() : null,
        ]);
    }
}
