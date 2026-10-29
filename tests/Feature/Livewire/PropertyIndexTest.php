<?php

use App\Enums\AmenityScope;
use App\Enums\BuildingStatus;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Livewire\Listings\PropertyIndex;
use App\Models\Amenity;
use App\Models\Property;
use Livewire\Livewire;

it('filters by pledge, condition, building status and distance to the sea', function () {
    $pledged = Property::factory()->type(PropertyType::Apartment, PropertyDeal::Pledge)->create([
        'title' => ['ka' => 'გირავნობის ბინა უნიკალური', 'en' => 'Pledged flat', 'ru' => 'Квартира в залоге'],
        'condition' => PropertyCondition::Renovated,
        'building_status' => BuildingStatus::UnderConstruction,
        'distance_to_sea_m' => 80,
    ]);
    Property::factory()->type(PropertyType::House, PropertyDeal::Sale)->create([
        'title' => ['ka' => 'გასაყიდი სახლი უნიკალური', 'en' => 'House for sale', 'ru' => 'Дом'],
        'condition' => PropertyCondition::BlackFrame,
        'building_status' => BuildingStatus::Old,
        'distance_to_sea_m' => 1800,
    ]);

    Livewire::test(PropertyIndex::class)
        ->set('deal', PropertyDeal::Pledge->value)
        ->assertSee('გირავნობის ბინა უნიკალური')
        ->assertDontSee('გასაყიდი სახლი უნიკალური')
        ->set('deal', '')
        ->set('condition', [PropertyCondition::Renovated->value])
        ->assertSee('გირავნობის ბინა უნიკალური')
        ->assertDontSee('გასაყიდი სახლი უნიკალური')
        ->set('condition', [])
        ->set('building', BuildingStatus::UnderConstruction->value)
        ->assertSee('გირავნობის ბინა უნიკალური')
        ->assertDontSee('გასაყიდი სახლი უნიკალური')
        ->set('building', '')
        ->set('sea', '100')
        ->assertSee('გირავნობის ბინა უნიკალური')
        ->assertDontSee('გასაყიდი სახლი უნიკალური');

    expect($pledged->distance_to_sea_m)->toBe(80);
});

it('filters by a property amenity and a keyword', function () {
    $pool = Amenity::factory()->scope(AmenityScope::Property)->create([
        'slug' => 'pool-test',
        'name' => ['ka' => 'აუზი', 'en' => 'Pool', 'ru' => 'Бассейн'],
    ]);
    $withPool = Property::factory()->type(PropertyType::House, PropertyDeal::Sale)->create([
        'title' => ['ka' => 'სახლი აუზით უნიკალური', 'en' => 'House with pool', 'ru' => 'Дом с бассейном'],
    ]);
    $withPool->features()->attach($pool);
    Property::factory()->type(PropertyType::Apartment, PropertyDeal::Sale)->create([
        'title' => ['ka' => 'ბინა აუზის გარეშე უნიკალური', 'en' => 'Flat without pool', 'ru' => 'Квартира'],
    ]);

    Livewire::test(PropertyIndex::class)
        ->set('amenities', ['pool-test'])
        ->assertSee('სახლი აუზით უნიკალური')
        ->assertDontSee('ბინა აუზის გარეშე უნიკალური')
        ->set('amenities', [])
        ->set('q', 'აუზით')
        ->assertSee('სახლი აუზით უნიკალური')
        ->assertDontSee('ბინა აუზის გარეშე უნიკალური');
});

it('indexes distance, condition and amenity slugs for Meilisearch', function () {
    $amenity = Amenity::factory()->scope(AmenityScope::Property)->create(['slug' => 'sea-view']);
    $property = Property::factory()->type(PropertyType::Apartment, PropertyDeal::Sale)->create([
        'distance_to_sea_m' => 120,
        'rooms' => 3,
        'floor' => 2,
        'condition' => PropertyCondition::Renovated,
    ]);
    $property->features()->attach($amenity);

    $document = $property->toSearchableArray();
    $settings = config('scout.meilisearch.index-settings.'.Property::class);

    expect($document['distance_to_sea_m'])->toBe(120)
        ->and($document['condition'])->toBe('renovated')
        ->and($document['rooms'])->toBe(3)
        ->and($document['floor'])->toBe(2)
        ->and($document['amenities'])->toBe(['sea-view'])
        ->and($settings['filterableAttributes'])->toContain('distance_to_sea_m', 'condition', 'building_status', 'amenities')
        ->and($settings['sortableAttributes'])->toContain('distance_to_sea_m');
});
