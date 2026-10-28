<?php

namespace Database\Factories;

use App\Models\ListingDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingDraft>
 */
class ListingDraftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'listing_type' => 'property',
            'listing_id' => null,
            'step' => 1,
            'data' => [],
        ];
    }
}
