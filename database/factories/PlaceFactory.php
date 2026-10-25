<?php

namespace Database\Factories;

use App\Enums\PlaceType;
use App\Models\Place;
use App\Models\User;
use Database\Factories\Concerns\ListingStates;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Place>
 */
class PlaceFactory extends Factory
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
            'type' => PlaceType::Cafe,
            'title' => ['ka' => 'კაფე ბულვარზე', 'en' => 'Boulevard Cafe', 'ru' => 'Кафе на бульваре'],
            'description' => [
                'ka' => 'ყავა, დესერტები და ხედი ზღვაზე.',
                'en' => 'Coffee, desserts and a view of the sea.',
                'ru' => 'Кофе, десерты и вид на море.',
            ],
            'content' => [
                'ka' => '<p>პატარა კაფე ზღვისპირა ბულვარზე, ფიჭვების ჩრდილში.</p>',
                'en' => '<p>A small cafe on the seaside boulevard, in the shade of the pines.</p>',
                'ru' => '<p>Небольшое кафе на приморском бульваре в тени сосен.</p>',
            ],
            'address' => Kobuleti::address(),
            ...Kobuleti::coordinates(),
            'opening_hours' => self::dailyHours('09:00', '23:00'),
            'phone' => Kobuleti::phone(),
            'price_level' => 2,
        ];
    }

    public function type(PlaceType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    /**
     * @return array<string, list<array{0: string, 1: string}>>
     */
    public static function dailyHours(string $opens, string $closes): array
    {
        return array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], [[$opens, $closes]]);
    }
}
