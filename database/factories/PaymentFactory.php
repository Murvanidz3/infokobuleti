<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gateway' => PaymentGateway::Fake,
            'gateway_order_id' => 'fake-'.Str::uuid(),
            'amount' => fake()->randomElement([5, 10, 15, 25, 49]),
            'currency' => Currency::GEL,
            'status' => PaymentStatus::Paid,
            'paid_at' => now()->subDay(),
        ];
    }
}
