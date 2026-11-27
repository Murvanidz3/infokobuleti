<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The archive migration has already run on a fresh test database, so a hotel row is inserted
 * by rolling that migration back and forward again.
 */
function withArchivedHotel(string $slug): void
{
    $migration = include database_path('migrations/2026_11_27_150000_archive_hotels_into_properties.php');
    $migration->down();

    DB::table('hotels')->insert([
        'user_id' => User::factory()->create()->id,
        'status' => 'published',
        'slug' => $slug,
        'title' => json_encode(['ka' => 'სასტუმრო', 'en' => 'Hotel', 'ru' => 'Отель']),
        'type' => 'hotel',
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->up();
}

it('redirects the old hotel index to daily-rent hotels', function () {
    $this->get('/hotels')->assertStatus(301)->assertRedirect(daily_rent_hotels_url());
    $this->get('/en/hotels')->assertStatus(301)->assertRedirect(daily_rent_hotels_url('en'));
    $this->get('/ru/hotels')->assertStatus(301)->assertRedirect(daily_rent_hotels_url('ru'));
});

it('redirects a known hotel slug to the copied property and an unknown slug to the index', function () {
    withArchivedHotel('sunrise-inn');

    $this->get('/hotels/sunrise-inn')->assertStatus(301)
        ->assertRedirect(localized_route('real-estate.show', ['property' => 'sunrise-inn']));
    $this->get('/en/hotels/sunrise-inn')->assertStatus(301)
        ->assertRedirect(localized_route('real-estate.show', ['property' => 'sunrise-inn'], 'en'));
    $this->get('/hotels/missing-hotel')->assertStatus(301)->assertRedirect(daily_rent_hotels_url());
});
