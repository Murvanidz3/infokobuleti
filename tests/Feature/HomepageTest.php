<?php

use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\Property;

dataset('locales', [
    'ka' => ['/', 'ka'],
    'en' => ['/en', 'en'],
    'ru' => ['/ru', 'ru'],
]);

it('renders the homepage in every locale', function (string $url, string $locale) {
    $this->get($url)
        ->assertOk()
        ->assertSee('<html lang="'.$locale.'"', false)
        ->assertSee(__('home.hero.title', [], $locale))
        ->assertSee(__('home.real_estate.title', [], $locale))
        ->assertSee(__('home.cta.button', [], $locale));
})->with('locales');

it('links every language version with hreflang alternates', function () {
    $this->get('/en?utm_source=newsletter')
        ->assertOk()
        ->assertSee('<link rel="alternate" hreflang="ka" href="'.url('/').'">', false)
        ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en').'">', false)
        ->assertSee('<link rel="alternate" hreflang="ru" href="'.url('/ru').'">', false)
        ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/').'">', false);
});

it('keeps the query string when switching language', function () {
    $this->get('/en?utm_source=newsletter')
        ->assertOk()
        ->assertSee('href="'.url('/ru').'?utm_source=newsletter"', false)
        ->assertSee('href="'.url('/').'?utm_source=newsletter"', false);
});

it('marks the active language in the switcher', function (string $url, string $locale) {
    $html = $this->get($url)->getContent();

    expect($html)->toMatch('/hreflang="'.$locale.'"\s+lang="'.$locale.'"\s+aria-current="true"/');
})->with('locales');

it('returns 404 for unsupported locale prefixes', function () {
    $this->get('/de')->assertNotFound();
});

it('renders a favorite button on each homepage property card', function () {
    Property::factory()->type(PropertyType::Apartment, PropertyDeal::Sale)->create([
        'title' => ['ka' => 'სატესტო ბინა გაყიდვა', 'en' => 'Test sale', 'ru' => 'Тест'],
    ]);
    Property::factory()->type(PropertyType::Apartment, PropertyDeal::Rent)->create([
        'title' => ['ka' => 'სატესტო ბინა ქირა', 'en' => 'Test rent', 'ru' => 'Тест'],
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'data-favorite'))->toBe(2)
        ->and(preg_match_all('/data-segment="(sale|rent)"/', $html))->toBe(2);
});

it('renders the homepage sections without a hotel booking block', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(__('home.real_estate.title'))
        ->assertSee(__('home.classifieds.title'))
        ->assertSee(__('home.jobs.title'))
        ->assertDontSee(__('home.hotels.title'));
});
