<?php

namespace Database\Seeders;

use App\Enums\CategoryModule;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Event;
use App\Models\Place;
use App\Models\User;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EventSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        $editor = User::role(Role::Admin->value)->firstOrFail();
        $categories = Category::query()->where('module', CategoryModule::Events)->pluck('id', 'slug');
        $places = Place::query()->get()->keyBy(fn (Place $place): string => $place->getTranslation('title', 'en'));

        foreach (require __DIR__.'/data/events.php' as $row) {
            /** @var Carbon $startsAt */
            $startsAt = $row['starts']();
            $place = $row['place'] !== null ? $places->get($row['place']) : null;
            $isAllDay = isset($row['days']);

            Event::factory()->create([
                'user_id' => $editor->id,
                'category_id' => $categories[$row['category']],
                'place_id' => $place?->id,
                'title' => $row['title'],
                'description' => $row['description'],
                'venue' => $row['venue'],
                'organizer' => $row['organizer'],
                ...($place !== null
                    ? ['location_id' => $place->location_id, 'lat' => $place->lat, 'lng' => $place->lng, 'address' => $place->getTranslations('address')]
                    : $this->placeAt($row['location'] ?? 'kobuleti-centre')),
                'starts_at' => $startsAt,
                'ends_at' => $isAllDay ? $startsAt->copy()->addDays($row['days']) : $startsAt->copy()->addHours($row['hours']),
                'is_all_day' => $isAllDay,
                'is_free' => $row['free'],
                'price_from' => $row['price'],
                'published_at' => min(now()->subDay(), $startsAt->copy()->subDays(10)),
            ]);
        }
    }
}
