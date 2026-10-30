<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Realistic Kobuleti demo content for local and staging. Never runs in production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(20261025);

        $this->call([
            DemoUserSeeder::class,
            PropertySeeder::class,
            ProjectSeeder::class,
            ClassifiedSeeder::class,
            VacancySeeder::class,
            PlaceSeeder::class,
            ArticleSeeder::class,
            EventSeeder::class,
            EngagementSeeder::class,
        ]);
    }
}
