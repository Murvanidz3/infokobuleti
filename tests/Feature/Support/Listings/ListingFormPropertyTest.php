<?php

use App\Enums\ListedBy;
use App\Enums\PropertyType;
use App\Support\Listings\ListingForm;
use Illuminate\Support\Facades\Validator;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function propertyDetailForm(array $overrides = []): array
{
    return array_replace([
        'title' => ['ka' => 'სათაური საკმარისად გრძელია', 'en' => '', 'ru' => ''],
        'description' => ['ka' => 'აღწერა რომელიც ოც სიმბოლოზე მეტია აუცილებლად აქ.', 'en' => '', 'ru' => ''],
        'property_type' => PropertyType::Apartment->value,
        'area_m2' => '48',
        'land_area_m2' => '',
        'rooms' => '2',
        'bedrooms' => '',
        'bathrooms' => '',
        'floor' => '3',
        'total_floors' => '5',
        'condition' => '',
        'building_status' => '',
        'cadastral_code' => '',
        'listed_by' => ListedBy::Owner->value,
        'features' => [],
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $form
 */
function propertyDetailsPass(array $form): bool
{
    return Validator::make(
        ['form' => $form],
        ListingForm::rules('property', 'details', $form),
    )->passes();
}

it('requires rooms and area for an apartment and rejects a yard', function () {
    expect(propertyDetailsPass(propertyDetailForm()))->toBeTrue()
        ->and(propertyDetailsPass(propertyDetailForm(['rooms' => ''])))->toBeFalse()
        ->and(propertyDetailsPass(propertyDetailForm(['area_m2' => ''])))->toBeFalse()
        ->and(propertyDetailsPass(propertyDetailForm(['land_area_m2' => '120'])))->toBeFalse();
});

it('requires only the plot area for land and rejects rooms and floor', function () {
    $land = propertyDetailForm([
        'property_type' => PropertyType::Land->value,
        'area_m2' => '',
        'land_area_m2' => '800',
        'rooms' => '',
        'floor' => '',
        'total_floors' => '',
    ]);

    expect(propertyDetailsPass($land))->toBeTrue()
        ->and(propertyDetailsPass(propertyDetailForm([
            'property_type' => PropertyType::Land->value,
            'area_m2' => '',
            'rooms' => '',
            'floor' => '',
            'total_floors' => '',
            'land_area_m2' => '',
        ])))->toBeFalse()
        ->and(propertyDetailsPass([...$land, 'rooms' => '3']))->toBeFalse()
        ->and(propertyDetailsPass([...$land, 'floor' => '1']))->toBeFalse();
});

it('stores a yard on a private house and drops the unit floor', function () {
    $form = propertyDetailForm([
        'property_type' => PropertyType::House->value,
        'floor' => '1',
        'land_area_m2' => '450',
    ]);

    expect(propertyDetailsPass($form))->toBeFalse()
        ->and(propertyDetailsPass(propertyDetailForm([
            'property_type' => PropertyType::House->value,
            'floor' => '',
            'land_area_m2' => '450',
        ])))->toBeTrue();

    $stored = ListingForm::attributes('property', [
        ...propertyDetailForm([
            'property_type' => PropertyType::House->value,
            'floor' => '',
            'land_area_m2' => '450',
        ]),
        'deal' => 'sale',
        'price' => '90000',
        'currency' => 'USD',
        'lat' => null,
        'lng' => null,
    ]);

    expect($stored['land_area_m2'])->toBe(450.0)
        ->and($stored['rooms'])->toBe(2)
        ->and($stored['floor'])->toBeNull();
});

it('rejects bedrooms on a commercial space and a hotel', function () {
    foreach ([PropertyType::Commercial, PropertyType::Hotel] as $type) {
        $form = propertyDetailForm([
            'property_type' => $type->value,
            'rooms' => '',
            'bedrooms' => '2',
        ]);

        expect(propertyDetailsPass($form))->toBeFalse();
    }
});

it('keeps hotel room count and drops a yard on an apartment when saving', function () {
    $hotel = propertyDetailForm([
        'property_type' => PropertyType::Hotel->value,
        'rooms' => '12',
        'bedrooms' => '',
        'floor' => '',
        'land_area_m2' => '600',
    ]);

    expect(propertyDetailsPass($hotel))->toBeTrue();

    $stored = ListingForm::attributes('property', [
        ...$hotel,
        'deal' => 'daily_rent',
        'price' => '180',
        'currency' => 'GEL',
        'lat' => null,
        'lng' => null,
    ]);

    expect($stored['rooms'])->toBe(12)
        ->and($stored['land_area_m2'])->toBe(600.0)
        ->and(array_key_exists('nearby', $stored))->toBeFalse();

    $apartment = ListingForm::attributes('property', [
        ...propertyDetailForm(['land_area_m2' => '40', 'rooms' => '2']),
        'deal' => 'sale',
        'price' => '50000',
        'currency' => 'USD',
        'lat' => null,
        'lng' => null,
    ]);

    expect($apartment['land_area_m2'])->toBeNull()
        ->and($apartment['rooms'])->toBe(2);
});

it('requires a private house to have rooms and area', function () {
    expect(propertyDetailsPass(propertyDetailForm([
        'property_type' => PropertyType::House->value,
        'floor' => '',
        'land_area_m2' => '300',
    ])))->toBeTrue()
        ->and(propertyDetailsPass(propertyDetailForm([
            'property_type' => PropertyType::House->value,
            'rooms' => '',
            'floor' => '',
        ])))->toBeFalse();
});
