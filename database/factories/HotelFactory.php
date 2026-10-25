<?php

namespace Database\Factories;

use App\Enums\HotelType;
use App\Models\Hotel;
use App\Models\User;
use Database\Factories\Concerns\ListingStates;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    use ListingStates;

    /** @var list<array{ka: string, en: string, ru: string}> */
    private const array NAMES = [
        ['ka' => 'ზღვის ბრიზი', 'en' => 'Sea Breeze', 'ru' => 'Морской бриз'],
        ['ka' => 'მზიური', 'en' => 'Mziuri', 'ru' => 'Мзиури'],
        ['ka' => 'თეთრი აფრა', 'en' => 'White Sail', 'ru' => 'Белый парус'],
        ['ka' => 'ციტრუსი', 'en' => 'Citrus', 'ru' => 'Цитрус'],
        ['ka' => 'კოლხეთი', 'en' => 'Kolkheti', 'ru' => 'Колхети'],
        ['ka' => 'ზურმუხტი', 'en' => 'Emerald', 'ru' => 'Изумруд'],
        ['ka' => 'ლაზური', 'en' => 'Lazuri', 'ru' => 'Лазури'],
        ['ka' => 'ფიჭვის ჩრდილი', 'en' => 'Pine Shade', 'ru' => 'Тень сосен'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(self::NAMES);
        $type = fake()->randomElement(HotelType::cases());

        return [
            ...$this->listingAttributes(),
            'user_id' => User::factory(),
            'title' => $name,
            'description' => [
                'ka' => 'მყუდრო ოთახები ზღვასთან ახლოს, ფიჭვნარით მოჩრდილულ ქუჩაზე. საუზმე ფასში შედის.',
                'en' => 'Cosy rooms close to the sea on a street shaded by pines. Breakfast included.',
                'ru' => 'Уютные номера рядом с морем на улице в тени сосен. Завтрак включён.',
            ],
            'address' => Kobuleti::address(),
            ...Kobuleti::coordinates(),
            'type' => $type,
            'stars' => $type === HotelType::Hotel ? fake()->numberBetween(2, 5) : null,
            'distance_to_sea_m' => fake()->randomElement([20, 50, 80, 120, 150, 200, 300, 450, 600]),
            'check_in_from' => '14:00',
            'check_out_until' => '12:00',
            'rooms_count' => fake()->numberBetween(4, 60),
            'season_start_month' => $type === HotelType::Hotel ? null : 5,
            'season_end_month' => $type === HotelType::Hotel ? null : 10,
            'contact_phone' => Kobuleti::phone(),
        ];
    }
}
