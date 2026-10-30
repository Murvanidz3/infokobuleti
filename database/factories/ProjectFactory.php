<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\Project;
use App\Models\User;
use Database\Factories\Concerns\ListingStates;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    use ListingStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['მზიური', 'ბულვარი', 'ციტრუსი', 'ზღვის კარი', 'პალმა']);

        return [
            ...$this->listingAttributes(),
            'user_id' => User::factory(),
            'title' => [
                'ka' => "საცხოვრებელი კომპლექსი {$name}",
                'en' => "{$name} residence",
                'ru' => "Жилой комплекс {$name}",
            ],
            'description' => [
                'ka' => 'მშენებარე საცხოვრებელი კომპლექსი ქობულეთში, ზღვასთან ახლოს. დეტალებისთვის დაგვიკავშირდით.',
                'en' => 'A residential project under construction in Kobuleti, close to the sea. Call for details.',
                'ru' => 'Жилой комплекс на стадии строительства в Кобулети, рядом с морем. Звоните, расскажем подробнее.',
            ],
            'developer' => fake()->randomElement(['მზიური დეველოპმენტი', 'აჭარა ბილდი', 'კობა გრუპი']),
            'address' => Kobuleti::address(),
            ...Kobuleti::coordinates(),
            'completion_on' => fake()->dateTimeBetween('+3 months', '+3 years')->format('Y-m-d'),
            'price_per_m2' => fake()->numberBetween(700, 1600),
            'currency' => Currency::USD,
            'distance_to_sea_m' => fake()->numberBetween(50, 800),
            'contact_name' => Kobuleti::firstName(),
            'contact_phone' => Kobuleti::phone(),
        ];
    }
}
