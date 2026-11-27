<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @return object{up: callable, down: callable}
 */
function propertyTaxonomyMigration(): object
{
    return include database_path('migrations/2026_11_27_160000_add_property_taxonomy_columns.php');
}

it('maps hotel businesses and daily-rent stays and restores the snapshot on the way down', function () {
    $user = User::factory()->create();
    $migration = propertyTaxonomyMigration();
    $migration->down();

    $insert = function (string $slug, string $deal, string $type) use ($user): int {
        return DB::table('properties')->insertGetId([
            'user_id' => $user->id,
            'status' => 'published',
            'slug' => $slug,
            'title' => json_encode(['ka' => $slug]),
            'deal' => $deal,
            'type' => $type,
            'price' => 100,
            'currency' => 'GEL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    $businessId = $insert('hotel-business', 'sale', 'hotel_business');
    $hotelId = $insert('daily-hotel', 'daily_rent', 'hotel');
    $apartmentId = $insert('daily-apartment', 'daily_rent', 'apartment');
    $houseId = $insert('daily-house', 'daily_rent', 'house');
    $landId = $insert('daily-land', 'daily_rent', 'land');
    $saleId = $insert('sale-apartment', 'sale', 'apartment');

    $archiveId = DB::table('archive_hotels')->insertGetId([
        'user_id' => $user->id,
        'status' => 'published',
        'slug' => 'archived-guesthouse',
        'title' => json_encode(['ka' => 'საოჯახო']),
        'type' => 'guesthouse',
        'stars' => 4,
        'rooms_count' => 6,
        'check_in_from' => '15:00',
        'check_out_until' => '11:00',
        'website' => 'https://stay.example',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('archive_hotel_properties')->insert([
        'hotel_id' => $archiveId,
        'property_id' => $hotelId,
        'hotel_slug' => 'archived-guesthouse',
        'property_slug' => 'daily-hotel',
    ]);

    $migration->up();

    $row = fn (int $id): object => DB::table('properties')->where('id', $id)->first();

    expect($row($businessId)->type)->toBe('commercial')
        ->and($row($businessId)->commercial_subtype)->toBe('hotel_business')
        ->and($row($businessId)->vacation_type)->toBeNull()
        ->and(DB::table('property_taxonomy_snapshots')->where('property_id', $businessId)->value('type'))->toBe('hotel_business')
        ->and($row($hotelId)->type)->toBe('hotel')
        ->and($row($hotelId)->vacation_type)->toBe('guesthouse')
        ->and($row($hotelId)->stars)->toBe(4)
        ->and($row($hotelId)->rooms_count)->toBe(6)
        ->and(substr((string) $row($hotelId)->check_in, 0, 5))->toBe('15:00')
        ->and(substr((string) $row($hotelId)->check_out, 0, 5))->toBe('11:00')
        ->and($row($hotelId)->website)->toBe('https://stay.example')
        ->and($row($apartmentId)->vacation_type)->toBe('apartment')
        ->and($row($apartmentId)->type)->toBe('apartment')
        ->and($row($houseId)->vacation_type)->toBe('house')
        ->and($row($landId)->vacation_type)->toBeNull()
        ->and($row($landId)->type)->toBe('land')
        ->and($row($saleId)->vacation_type)->toBeNull();

    $migration->down();

    expect(Schema::hasColumn('properties', 'vacation_type'))->toBeFalse()
        ->and(Schema::hasTable('property_taxonomy_snapshots'))->toBeFalse()
        ->and($row($businessId)->type)->toBe('hotel_business')
        ->and($row($apartmentId)->type)->toBe('apartment')
        ->and($row($hotelId)->type)->toBe('hotel')
        ->and($row($hotelId)->deal)->toBe('daily_rent');
});
