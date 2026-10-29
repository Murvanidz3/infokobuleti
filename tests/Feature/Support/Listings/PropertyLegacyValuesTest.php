<?php

use App\Enums\AmenityScope;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\Amenity;
use App\Models\Property;
use App\Support\Listings\PropertyLegacyValues;
use Illuminate\Support\Facades\DB;

it('maps hotel businesses and needs-renovation rows onto the current enums', function () {
    $property = Property::factory()->type(PropertyType::House, PropertyDeal::Sale)->create([
        'condition' => PropertyCondition::OldRenovation,
    ]);

    DB::table('properties')->where('id', $property->id)->update([
        'type' => 'hotel_business',
        'condition' => 'needs_renovation',
    ]);

    PropertyLegacyValues::apply();

    $row = DB::table('properties')->where('id', $property->id)->first();

    expect($row->type)->toBe('hotel')
        ->and($row->condition)->toBe('old_renovation');
});

it('rolls new values back to ones the previous enums accept', function () {
    $hotel = Property::factory()->type(PropertyType::Hotel, PropertyDeal::DailyRent)->create();
    $country = Property::factory()->type(PropertyType::CountryHouse, PropertyDeal::Sale)->create([
        'condition' => PropertyCondition::Renovated,
    ]);
    $pledged = Property::factory()->type(PropertyType::Apartment, PropertyDeal::Pledge)->create();

    PropertyLegacyValues::rollback();

    expect(DB::table('properties')->where('id', $hotel->id)->value('type'))->toBe('hotel_business')
        ->and(DB::table('properties')->where('id', $country->id)->value('type'))->toBe('house')
        ->and(DB::table('properties')->where('id', $country->id)->value('condition'))->toBe('newly_renovated')
        ->and(DB::table('properties')->where('id', $pledged->id)->value('deal'))->toBe('sale');
});

it('adds the ss.ge property amenities during migration', function () {
    $internet = Amenity::query()->where('scope', AmenityScope::Property)->where('slug', 'internet')->first();

    expect(Amenity::query()->where('scope', AmenityScope::Property)->where('slug', 'iron-door')->exists())->toBeTrue()
        ->and(Amenity::query()->where('scope', AmenityScope::Property)->where('slug', 'double-glazing')->exists())->toBeTrue()
        ->and($internet)->not->toBeNull()
        ->and($internet->getTranslation('name', 'en'))->toBe('Internet / Wi-Fi')
        ->and($internet->getTranslation('name', 'ka'))->toBe('ინტერნეტი / Wi-Fi');
});
