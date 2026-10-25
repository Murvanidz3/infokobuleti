<?php

namespace Database\Factories;

use App\Enums\PromotionType;
use App\Models\Promotion;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promotable_type' => 'property',
            'promotable_id' => Property::factory(),
            'user_id' => User::factory(),
            'type' => PromotionType::Vip,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(6),
        ];
    }

    public function type(PromotionType $type): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'ends_at' => $type === PromotionType::Bump ? null : now()->addDays(6),
        ]);
    }
}
