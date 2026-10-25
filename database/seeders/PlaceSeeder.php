<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Enums\PlaceType;
use App\Enums\Role;
use App\Models\BusinessProfile;
use App\Models\Place;
use App\Models\User;
use Database\Factories\PlaceFactory;
use Database\Factories\Support\Kobuleti;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;

/**
 * 25 city guide entries, written by the content team; restaurants with a profile belong to their owners.
 */
class PlaceSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        $editor = User::role(Role::Admin->value)->firstOrFail();
        $restaurants = BusinessProfile::query()->where('type', BusinessType::Restaurant)->get()->keyBy('name');

        foreach (require __DIR__.'/data/places.php' as $row) {
            $type = PlaceType::from($row['type']);
            $profile = $restaurants->get($row['title']['ka']);
            $location = $this->location($row['location']);
            $hours = $row['hours'] === 'season' ? ['10:00', '22:00'] : $row['hours'];

            Place::factory()->create([
                'user_id' => $profile->user_id ?? $editor->id,
                'business_profile_id' => $profile?->id,
                'type' => $type,
                'title' => $row['title'],
                'description' => $row['description'],
                'content' => $row['content'],
                'location_id' => $location->id,
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'address' => $profile?->getTranslations('address') ?: $location->getTranslations('name'),
                'opening_hours' => $hours === null ? null : PlaceFactory::dailyHours(...$hours),
                'phone' => in_array($type, [PlaceType::Restaurant, PlaceType::Cafe, PlaceType::Museum, PlaceType::Service], true) ? ($profile->phone ?? Kobuleti::phone()) : null,
                'price_level' => $row['price'],
            ]);
        }
    }
}
