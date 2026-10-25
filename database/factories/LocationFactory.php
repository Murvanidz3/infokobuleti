<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'type' => LocationType::Village,
            'name' => Kobuleti::same($name),
            ...Kobuleti::coordinates(),
            'sort' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
