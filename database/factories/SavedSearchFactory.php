<?php

namespace Database\Factories;

use App\Enums\ListingModule;
use App\Enums\SearchFrequency;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedSearch>
 */
class SavedSearchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'module' => ListingModule::Properties,
            'name' => '2-ოთახიანი ბინა ზღვასთან',
            'filters' => ['deal' => 'sale', 'type' => 'apartment', 'rooms' => 2, 'price_max' => 70000, 'currency' => 'USD'],
            'channels' => ['email'],
            'frequency' => SearchFrequency::Daily,
            'is_active' => true,
        ];
    }
}
