<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data the site needs to work: roles, locations, categories, amenities, static pages and the price list. Safe to re-run.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LocationSeeder::class,
            CategorySeeder::class,
            AmenitySeeder::class,
            PageSeeder::class,
            PricePlanSeeder::class,
        ]);
    }
}
