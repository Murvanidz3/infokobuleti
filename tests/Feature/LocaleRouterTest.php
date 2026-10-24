<?php

use App\Services\LocaleRouter;

beforeEach(function () {
    $this->router = app(LocaleRouter::class);
});

it('keeps the default locale unprefixed', function () {
    expect($this->router->routeName('home', 'ka'))->toBe('home')
        ->and($this->router->routeName('home', 'en'))->toBe('en.home')
        ->and($this->router->routeName('home', 'ru'))->toBe('ru.home');
});

it('strips the locale prefix from route names', function () {
    expect($this->router->baseName('en.home'))->toBe('home')
        ->and($this->router->baseName('ru.styleguide'))->toBe('styleguide')
        ->and($this->router->baseName('home'))->toBe('home');
});

it('builds localized URLs', function () {
    expect(localized_route('home', locale: 'ka'))->toBe(url('/'))
        ->and(localized_route('home', locale: 'en'))->toBe(url('/en'))
        ->and(localized_route('styleguide', locale: 'ru', absolute: false))->toBe('/ru/_styleguide');
});

it('knows which locales are supported', function () {
    expect($this->router->isSupported('ka'))->toBeTrue()
        ->and($this->router->isSupported('en'))->toBeTrue()
        ->and($this->router->isSupported('ru'))->toBeTrue()
        ->and($this->router->isSupported('de'))->toBeFalse();
});

it('sets the application locale from the URL', function (string $url, string $locale) {
    $this->get($url)->assertOk();

    expect(app()->getLocale())->toBe($locale);
})->with([
    ['/', 'ka'],
    ['/en', 'en'],
    ['/ru', 'ru'],
]);
