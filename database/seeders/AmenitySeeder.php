<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/amenities.php' as $scope => $amenities) {
            foreach ($amenities as $sort => [$slug, $group, $icon, $ka, $en, $ru]) {
                Amenity::query()->updateOrCreate(['scope' => $scope, 'slug' => $slug], [
                    'group' => $group,
                    'icon' => $icon,
                    'name' => ['ka' => $ka, 'en' => $en, 'ru' => $ru],
                    'sort' => $sort,
                    'is_filterable' => true,
                ]);
            }
        }
    }
}
