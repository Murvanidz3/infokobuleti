<?php

namespace Database\Seeders;

use App\Enums\AmenityScope;
use App\Enums\BusinessType;
use App\Enums\ListedBy;
use App\Enums\ListingStatus;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\Amenity;
use App\Models\BusinessProfile;
use App\Models\Property;
use Database\Factories\Support\Kobuleti;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;

/**
 * 60 properties: apartments for sale and rent near the sea, houses and land in the villages.
 */
class PropertySeeder extends Seeder
{
    use SeedsDemoData;

    /** @var list<array{0: string, 1: string, 2: int}> type, deal, count */
    private const array PLAN = [
        ['apartment', 'sale', 20], ['apartment', 'rent', 8], ['apartment', 'daily_rent', 6], ['apartment', 'pledge', 2],
        ['house', 'sale', 5], ['house', 'rent', 2], ['country_house', 'sale', 4], ['land', 'sale', 5],
        ['commercial', 'sale', 1], ['commercial', 'rent', 2], ['hotel', 'sale', 2], ['hotel', 'daily_rent', 3],
    ];

    /** @var array<string, list<string>> weighted by repetition */
    private const array LOCATIONS = [
        'apartment' => ['kobuleti-centre', 'kobuleti-centre', 'kobuleti-centre', 'north-kobuleti', 'north-kobuleti', 'north-kobuleti', 'south-kobuleti', 'chakvi', 'tsikhisdziri'],
        'house' => ['south-kobuleti', 'tsikhisdziri', 'bobokvati', 'chaisubani', 'mukhaestate', 'chakvi', 'ochkhamuri'],
        'land' => ['bobokvati', 'pichvnari', 'dagva', 'buknari', 'sakhalvasho', 'kvirike', 'chaisubani'],
        'country_house' => ['south-kobuleti', 'tsikhisdziri', 'bobokvati', 'chaisubani', 'chakvi'],
        'commercial' => ['kobuleti-centre', 'north-kobuleti'],
        'hotel' => ['north-kobuleti', 'kobuleti-centre', 'tsikhisdziri'],
    ];

    /** @var array<int, ListingStatus> */
    private const array STATUSES = [10 => ListingStatus::Pending, 25 => ListingStatus::Pending, 40 => ListingStatus::Draft, 50 => ListingStatus::Expired];

    public function run(): void
    {
        $agencies = BusinessProfile::query()->where('type', BusinessType::Agency)->get();
        $features = Amenity::query()->where('scope', AmenityScope::Property)->pluck('id');
        $position = 0;

        foreach (self::PLAN as [$type, $deal, $count]) {
            for ($i = 0; $i < $count; $i++, $position++) {
                $agency = fake()->boolean(50) ? $agencies->random() : null;
                $location = fake()->randomElement(self::LOCATIONS[$type]);
                $inTown = in_array($location, self::TOWN_LOCATIONS, true);

                $property = $this->withDemoStatus(Property::factory(), $position, self::STATUSES)
                    ->type(PropertyType::from($type), PropertyDeal::from($deal))
                    ->create([
                        'user_id' => $agency->user_id ?? $this->individuals()->random()->id,
                        'business_profile_id' => $agency?->id,
                        'listed_by' => $agency ? ListedBy::Agency : ListedBy::Owner,
                        ...$this->placeAt($location),
                        'distance_to_sea_m' => $inTown ? fake()->numberBetween(5, 120) * 10 : fake()->numberBetween(8, 50) * 100,
                        'contact_name' => Kobuleti::firstName(),
                        'contact_phone' => $agency->phone ?? Kobuleti::phone(),
                    ]);

                if ($type !== 'land') {
                    $property->features()->attach($features->random(fake()->numberBetween(3, 7))->all());
                }
            }
        }
    }
}
