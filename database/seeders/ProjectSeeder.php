<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Models\Project;
use Database\Factories\Support\Kobuleti;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;

/**
 * A handful of new-construction projects for the /real-estate/projects section.
 */
class ProjectSeeder extends Seeder
{
    use SeedsDemoData;

    /** @var list<string> */
    private const array LOCATIONS = ['kobuleti-centre', 'north-kobuleti', 'south-kobuleti', 'chakvi', 'tsikhisdziri', 'bobokvati'];

    public function run(): void
    {
        foreach (self::LOCATIONS as $index => $location) {
            $status = match ($index) {
                4 => ListingStatus::Pending,
                5 => ListingStatus::Draft,
                default => null,
            };

            $factory = Project::factory();

            if ($status !== null) {
                $factory = $this->withDemoStatus($factory, $index, [$index => $status]);
            }

            $factory->create([
                'user_id' => $this->individuals()->random()->id,
                ...$this->placeAt($location),
                'contact_name' => Kobuleti::firstName(),
                'contact_phone' => Kobuleti::phone(),
            ]);
        }
    }
}
