<?php

use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\Property;

it('serves an seo browse url for a property type and deal', function () {
    Property::factory()->type(PropertyType::Apartment, PropertyDeal::Pledge)->create([
        'title' => ['ka' => 'გირავნობის ბინა მარშრუტი', 'en' => 'Routed pledge', 'ru' => 'Залог'],
    ]);
    Property::factory()->type(PropertyType::Apartment, PropertyDeal::Sale)->create([
        'title' => ['ka' => 'გასაყიდი ბინა მარშრუტი', 'en' => 'Routed sale', 'ru' => 'Продажа'],
    ]);

    $this->get('/real-estate/apartment/pledge')
        ->assertOk()
        ->assertSee('გირავნობის ბინა მარშრუტი')
        ->assertDontSee('გასაყიდი ბინა მარშრუტი')
        ->assertSee(__('site.real_estate.browse', [
            'deal' => __('enums.property_deal.pledge'),
            'type' => __('enums.property_type.apartment'),
        ]));
});

it('serves a type url and still opens a listing slug', function () {
    $property = Property::factory()->type(PropertyType::CountryHouse, PropertyDeal::Sale)->create([
        'title' => ['ka' => 'აგარაკი მანდარინებით უნიკალური', 'en' => 'Mandarin house', 'ru' => 'Дача'],
    ]);

    $this->get('/real-estate/country_house')
        ->assertOk()
        ->assertSee('აგარაკი მანდარინებით უნიკალური');

    $this->get('/en/real-estate/hotel/daily_rent')->assertOk();

    $this->get('/real-estate/'.$property->slug)
        ->assertOk()
        ->assertSee('აგარაკი მანდარინებით უნიკალური');
});

it('does not let a listing slug shadow a browse path', function () {
    $property = Property::factory()->create([
        'title' => ['ka' => 'apartment', 'en' => 'apartment', 'ru' => 'apartment'],
    ]);

    expect($property->slug)->toBe('apartment-2');

    $this->get('/real-estate/apartment')->assertOk();
});
