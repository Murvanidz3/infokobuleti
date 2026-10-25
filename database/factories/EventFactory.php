<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Database\Factories\Concerns\ListingStates;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    use ListingStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(fake()->numberBetween(2, 60))->setTime(19, 0);

        return [
            ...$this->listingAttributes(),
            'user_id' => User::factory(),
            'title' => ['ka' => 'ჯაზის საღამო ბულვარზე', 'en' => 'Jazz evening on the boulevard', 'ru' => 'Джазовый вечер на бульваре'],
            'description' => [
                'ka' => 'ცოცხალი მუსიკა ზღვის პირას, შესვლა თავისუფალია.',
                'en' => 'Live music by the sea, free entry.',
                'ru' => 'Живая музыка у моря, вход свободный.',
            ],
            'venue' => ['ka' => 'ცენტრალური პარკის ამფითეატრი', 'en' => 'Central Park amphitheatre', 'ru' => 'Амфитеатр Центрального парка'],
            'address' => Kobuleti::address(),
            ...Kobuleti::coordinates(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
            'is_free' => true,
        ];
    }
}
