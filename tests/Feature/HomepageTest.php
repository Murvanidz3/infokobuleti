<?php

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
        ->assertSee(__('home.hotels.title', [], $locale))
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

it('renders favorite buttons with their saved state for the delegated toggle', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'data-favorite'))->toBe(12)
        ->and(preg_match_all('/data-favorite\s+aria-pressed="true"/', $html))->toBe(1);
});

it('tags property cards with their deal for the segmented filter', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(preg_match_all('/data-segment="(sale|rent)"/', $html))->toBe(6)
        ->and($html)->toContain('data-carousel-track');
});

it('renders the hotel, property, classified and job demo content', function () {
    app()->setLocale('ka');

    $this->get('/')
        ->assertOk()
        ->assertSee(__('demo.hotels.h1.name'))
        ->assertSee(__('demo.properties.r1.title'))
        ->assertSee(__('demo.classifieds.c1.title'))
        ->assertSee(__('demo.jobs.j1.title'))
        ->assertSee(__('demo.guide.g1.title'))
        ->assertSee(__('demo.news.n1.title'));
});
