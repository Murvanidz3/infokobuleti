<?php

namespace Database\Factories;

use App\Enums\CategoryModule;
use App\Enums\Currency;
use App\Enums\ItemCondition;
use App\Models\Category;
use App\Models\Classified;
use App\Models\User;
use App\Support\AttributeSchema;
use Database\Factories\Concerns\ListingStates;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classified>
 */
class ClassifiedFactory extends Factory
{
    use ListingStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ...$this->listingAttributes(),
            'user_id' => User::factory(),
            'category_id' => Category::factory()->module(CategoryModule::Classifieds),
            'title' => [
                'ka' => 'სასადილო მაგიდა 6 სკამით',
                'en' => 'Dining table with 6 chairs',
                'ru' => 'Обеденный стол с 6 стульями',
            ],
            'description' => [
                'ka' => 'კარგ მდგომარეობაშია, გადატანა შესაძლებელია ქობულეთის ფარგლებში.',
                'en' => 'In good condition, delivery possible within Kobuleti.',
                'ru' => 'В хорошем состоянии, возможна доставка по Кобулети.',
            ],
            'address' => Kobuleti::address(),
            ...Kobuleti::coordinates(),
            'price' => fake()->numberBetween(2, 200) * 10,
            'currency' => Currency::GEL,
            'negotiable' => fake()->boolean(30),
            'condition' => fake()->randomElement(ItemCondition::cases()),
            'attribute_values' => [],
            'contact_name' => Kobuleti::firstName(),
            'contact_phone' => Kobuleti::phone(),
        ];
    }

    /**
     * Fills attribute_values with valid random values for the category's schema.
     */
    public function withAttributesFor(Category $category): static
    {
        return $this->state(fn () => [
            'category_id' => $category->getKey(),
            'attribute_values' => self::fakeValues($category->schema()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function fakeValues(AttributeSchema $schema): array
    {
        $values = [];

        foreach ($schema->fields() as $field) {
            $options = array_column($field['options'] ?? [], 'value');

            $values[$field['key']] = match ($field['type']) {
                'number' => fake()->numberBetween((int) ($field['min'] ?? 0), (int) ($field['max'] ?? 1000)),
                'select' => fake()->randomElement($options),
                'multiselect' => fake()->randomElements($options, min(2, count($options))),
                'boolean' => fake()->boolean(),
                default => fake()->word(),
            };
        }

        return $values;
    }
}
