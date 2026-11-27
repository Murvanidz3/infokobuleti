<?php

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @return object{up: callable, down: callable}
 */
function hotelArchiveMigration(): object
{
    return include database_path('migrations/2026_11_27_150000_archive_hotels_into_properties.php');
}

it('copies hotels into daily-rent properties and restores them on the way down', function () {
    $user = User::factory()->create();
    Property::factory()->create(['slug' => 'sea-breeze', 'user_id' => $user->id]);

    $migration = hotelArchiveMigration();
    $migration->down();

    $pricedId = DB::table('hotels')->insertGetId([
        'user_id' => $user->id,
        'status' => 'published',
        'slug' => 'sea-breeze',
        'title' => json_encode(['ka' => 'ზღვის ნიავი', 'en' => 'Sea breeze', 'ru' => '']),
        'description' => json_encode(['ka' => 'აღწერა', 'en' => 'Description', 'ru' => '']),
        'address' => json_encode(['ka' => 'ბულვარი', 'en' => 'Boulevard', 'ru' => '']),
        'type' => 'guesthouse',
        'stars' => 4,
        'price_from' => null,
        'rooms_count' => 6,
        'contact_phone' => '+995555111222',
        'contact_email' => 'stay@example.com',
        'website' => 'https://example.com',
        'distance_to_sea_m' => 80,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('room_types')->insert([
        'hotel_id' => $pricedId,
        'name' => json_encode(['ka' => 'სტანდარტი']),
        'base_price' => 180,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('room_types')->insert([
        'hotel_id' => $pricedId,
        'name' => json_encode(['ka' => 'იაფი']),
        'base_price' => 95,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $plainId = DB::table('hotels')->insertGetId([
        'user_id' => $user->id,
        'status' => 'published',
        'slug' => 'plain-stay',
        'title' => json_encode(['ka' => 'უბრალო', 'en' => 'Plain', 'ru' => '']),
        'type' => 'hotel',
        'price_from' => 140,
        'contact_phone' => '+995555333444',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $amenityId = DB::table('amenities')->insertGetId([
        'scope' => 'hotel',
        'name' => json_encode(['ka' => 'აუზი', 'en' => 'Pool', 'ru' => 'Бассейн']),
        'slug' => 'pool-archive',
        'sort' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('amenityables')->insert([
        'amenity_id' => $amenityId,
        'amenityable_type' => 'hotel',
        'amenityable_id' => $pricedId,
    ]);

    DB::table('favorites')->insert([
        'user_id' => $user->id,
        'favoritable_type' => 'hotel',
        'favoritable_id' => $pricedId,
        'created_at' => now(),
    ]);
    DB::table('reviews')->insert([
        'user_id' => $user->id,
        'reviewable_type' => 'hotel',
        'reviewable_id' => $pricedId,
        'rating' => 9,
        'body' => 'Great stay',
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('saved_searches')->insert([
        'user_id' => $user->id,
        'module' => 'hotels',
        'name' => 'By the sea',
        'filters' => json_encode(['stars' => [4], 'q' => 'sea']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('listing_drafts')->insert([
        'user_id' => $user->id,
        'listing_type' => 'hotel',
        'listing_id' => $pricedId,
        'step' => 2,
        'data' => json_encode([
            'title' => ['ka' => 'დრაფტი', 'en' => '', 'ru' => ''],
            'rooms_count' => 6,
            'amenities' => [$amenityId],
            'room' => ['base_price' => '95'],
            'contact_phone' => '+995555111222',
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('settings')->updateOrInsert(
        ['key' => 'homepage.featured_hotels'],
        ['value' => json_encode([$pricedId, 999999]), 'created_at' => now(), 'updated_at' => now()],
    );
    DB::table('price_plans')->insert([
        'code' => 'featured-hotel-30',
        'product' => 'featured_hotel',
        'name' => json_encode(['ka' => 'რჩეული', 'en' => 'Featured', 'ru' => 'Избранный']),
        'description' => json_encode(['ka' => 'ძველი', 'en' => 'Old', 'ru' => 'Старый']),
        'price' => 150,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->up();

    $copied = Property::query()->where('slug', 'sea-breeze-2')->firstOrFail();
    $plain = Property::query()->where('slug', 'plain-stay')->firstOrFail();

    expect($copied->deal->value)->toBe('daily_rent')
        ->and($copied->type->value)->toBe('hotel')
        ->and((float) $copied->price)->toBe(95.0)
        ->and($copied->currency->value)->toBe('GEL')
        ->and($copied->rooms)->toBe(6)
        ->and($copied->contact_phone)->toBe('+995555111222')
        ->and($copied->getTranslation('title', 'ka'))->toBe('ზღვის ნიავი')
        ->and((float) $plain->price)->toBe(140.0)
        ->and(Schema::hasTable('hotels'))->toBeFalse()
        ->and(Schema::hasTable('archive_hotels'))->toBeTrue()
        ->and(DB::table('archive_hotels')->where('id', $pricedId)->value('contact_email'))->toBe('stay@example.com')
        ->and(DB::table('archive_hotel_properties')->where('hotel_id', $pricedId)->value('property_slug'))->toBe('sea-breeze-2')
        ->and(DB::table('favorites')->where('favoritable_id', $copied->id)->value('favoritable_type'))->toBe('property')
        ->and(DB::table('reviews')->where('reviewable_type', 'hotel')->count())->toBe(0)
        ->and(DB::table('archive_hotel_reviews')->where('reviewable_id', $pricedId)->value('rating'))->toBe(9)
        ->and(DB::table('amenityables')->where('amenityable_id', $copied->id)->value('amenityable_type'))->toBe('property')
        ->and(DB::table('saved_searches')->value('module'))->toBe('properties')
        ->and(json_decode((string) DB::table('saved_searches')->value('filters'), true)['type'])->toBe(['hotel'])
        ->and(json_decode((string) DB::table('saved_searches')->value('filters'), true)['stars'])->toBe([4])
        ->and(DB::table('listing_drafts')->value('listing_type'))->toBe('property')
        ->and((int) DB::table('listing_drafts')->value('listing_id'))->toBe($copied->id)
        ->and(json_decode((string) DB::table('settings')->where('key', 'homepage.featured_hotels')->value('value'), true))->toBe([$copied->id])
        ->and((bool) DB::table('price_plans')->where('code', 'featured-hotel-30')->value('is_active'))->toBeFalse();

    $migration->down();

    expect(Schema::hasTable('hotels'))->toBeTrue()
        ->and(Schema::hasTable('archive_hotels'))->toBeFalse()
        ->and(Property::query()->where('slug', 'sea-breeze-2')->exists())->toBeFalse()
        ->and(DB::table('hotels')->where('id', $pricedId)->value('contact_email'))->toBe('stay@example.com')
        ->and(DB::table('favorites')->where('favoritable_id', $pricedId)->value('favoritable_type'))->toBe('hotel')
        ->and(DB::table('reviews')->where('reviewable_id', $pricedId)->value('body'))->toBe('Great stay')
        ->and(DB::table('saved_searches')->value('module'))->toBe('hotels')
        ->and(DB::table('listing_drafts')->value('listing_type'))->toBe('hotel')
        ->and((int) DB::table('listing_drafts')->value('listing_id'))->toBe($pricedId)
        ->and((bool) DB::table('price_plans')->where('code', 'featured-hotel-30')->value('is_active'))->toBeTrue()
        ->and(Property::query()->where('slug', 'sea-breeze')->exists())->toBeTrue();

    $migration->up();
});
