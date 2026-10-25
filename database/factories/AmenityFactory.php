<?php

namespace Database\Factories;

use App\Enums\AmenityScope;
use App\Models\Amenity;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'scope' => AmenityScope::Hotel,
            'name' => Kobuleti::same(ucfirst($name)),
            'slug' => Str::slug($name),
            'icon' => 'check',
            'sort' => fake()->numberBetween(0, 50),
            'is_filterable' => true,
        ];
    }

    public function scope(AmenityScope $scope): static
    {
        return $this->state(fn () => ['scope' => $scope]);
    }
}
