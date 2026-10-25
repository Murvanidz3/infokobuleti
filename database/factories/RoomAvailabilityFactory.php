<?php

namespace Database\Factories;

use App\Models\RoomAvailability;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomAvailability>
 */
class RoomAvailabilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'date' => fake()->unique()->dateTimeBetween('+1 day', '+90 days')->format('Y-m-d'),
            'is_closed' => true,
            'price_override' => null,
        ];
    }

    public function priced(float $price): static
    {
        return $this->state(fn () => ['is_closed' => false, 'price_override' => $price]);
    }
}
