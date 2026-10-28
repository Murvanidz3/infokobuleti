<?php

namespace Database\Factories;

use App\Models\BusinessSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessSubscription>
 */
class BusinessSubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'listings_limit' => 50,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(29),
        ];
    }
}
