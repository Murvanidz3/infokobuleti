<?php

use App\Contracts\ExchangeRates;
use App\Enums\Currency;
use App\Enums\PropertyType;
use App\Models\Classified;
use App\Models\Property;
use App\Services\Currency\FixedExchangeRates;

beforeEach(function () {
    $this->app->instance(ExchangeRates::class, new FixedExchangeRates(['USD' => 2.70, 'EUR' => 3.00]));
});

it('computes price_per_m2 in the database', function () {
    $property = Property::factory()->create(['price' => 58000, 'currency' => Currency::USD, 'area_m2' => 64]);

    expect($property->fresh()->price_per_m2)->toBe(906.25);
});

it('recomputes price_per_m2 when price or area change', function () {
    $property = Property::factory()->create(['price' => 50000, 'area_m2' => 50]);

    $property->update(['price' => 60000]);
    expect($property->fresh()->price_per_m2)->toBe(1200.0);

    $property->update(['area_m2' => 80]);
    expect($property->fresh()->price_per_m2)->toBe(750.0);
});

it('computes price_per_m2 from the plot when there is no built area', function () {
    $land = Property::factory()->type(PropertyType::Land)->create([
        'area_m2' => null,
        'land_area_m2' => 500,
        'price' => 25000,
    ]);

    expect($land->fresh()->price_per_m2)->toBe(50.0);
});

it('leaves price_per_m2 empty when neither area is set', function () {
    $land = Property::factory()->type(PropertyType::Land)->create([
        'area_m2' => null,
        'land_area_m2' => null,
    ]);

    expect($land->fresh()->price_per_m2)->toBeNull();
});

it('normalizes prices to lari for filtering and sorting', function (float $price, Currency $currency, float $gel) {
    expect(Property::factory()->create(['price' => $price, 'currency' => $currency])->price_gel)->toBe($gel);
})->with([
    'lari stays lari' => [1400, Currency::GEL, 1400.0],
    'dollars' => [58000, Currency::USD, 156600.0],
    'euros' => [100000, Currency::EUR, 300000.0],
]);

it('updates price_gel when the price or currency changes', function () {
    $classified = Classified::factory()->create(['price' => 100, 'currency' => Currency::GEL]);

    $classified->update(['currency' => Currency::USD]);

    expect($classified->fresh()->price_gel)->toBe(270.0);
});

it('keeps price_gel empty for classifieds priced by agreement', function () {
    expect(Classified::factory()->create(['price' => null])->price_gel)->toBeNull();
});
