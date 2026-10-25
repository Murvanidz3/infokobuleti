<?php

namespace Database\Factories;

use App\Models\DailyStat;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyStat>
 */
class DailyStatFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'statable_type' => 'property',
            'statable_id' => Property::factory(),
            'date' => fake()->unique()->dateTimeBetween('-30 days')->format('Y-m-d'),
            'views' => fake()->numberBetween(0, 300),
            'phone_reveals' => fake()->numberBetween(0, 20),
            'favorites' => fake()->numberBetween(0, 10),
            'messages' => fake()->numberBetween(0, 5),
        ];
    }
}
