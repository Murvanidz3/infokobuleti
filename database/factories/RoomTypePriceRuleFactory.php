<?php

namespace Database\Factories;

use App\Models\RoomType;
use App\Models\RoomTypePriceRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomTypePriceRule>
 */
class RoomTypePriceRuleFactory extends Factory
{
    /**
     * High season: July and August of next summer.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = now()->month > 8 ? now()->year + 1 : now()->year;

        return [
            'room_type_id' => RoomType::factory(),
            'name' => ['ka' => 'მაღალი სეზონი', 'en' => 'High season', 'ru' => 'Высокий сезон'],
            'starts_on' => "{$year}-07-01",
            'ends_on' => "{$year}-08-31",
            'price' => fake()->numberBetween(180, 380),
            'min_nights' => fake()->randomElement([1, 2, 3]),
        ];
    }
}
