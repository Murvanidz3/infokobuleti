<?php

namespace Database\Factories;

use App\Enums\CategoryModule;
use App\Models\Category;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module' => CategoryModule::Classifieds,
            'name' => Kobuleti::same(ucfirst(fake()->unique()->words(2, true))),
            'icon' => 'tag',
            'sort' => fake()->numberBetween(0, 50),
            'attribute_schema' => null,
            'is_active' => true,
        ];
    }

    public function module(CategoryModule $module): static
    {
        return $this->state(fn () => ['module' => $module]);
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    public function withSchema(array $fields): static
    {
        return $this->state(fn () => ['attribute_schema' => $fields]);
    }
}
