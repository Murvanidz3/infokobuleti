<?php

use App\Contracts\ExchangeRates;
use App\Enums\Currency;
use App\Services\Info\UsefulInfo;
use App\Services\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function fakeInfoApis(): void
{
    Http::fake([
        // The marine host first: "api.open-meteo.com/*" would match it too.
        'marine-api.open-meteo.com/*' => Http::response(['current' => ['sea_surface_temperature' => 23.2]]),
        'api.open-meteo.com/*' => Http::response(['current' => ['temperature_2m' => 21.6, 'weather_code' => 2, 'wind_speed_10m' => 3.4]]),
        'nbg.gov.ge/*' => Http::response([['date' => '2026-10-25', 'currencies' => [
            ['code' => 'USD', 'quantity' => 1, 'rate' => 2.7135],
            ['code' => 'EUR', 'quantity' => 1, 'rate' => 3.1420],
            ['code' => 'TRY', 'quantity' => 10, 'rate' => 0.7890],
        ]]]),
    ]);
}

it('fetches weather, sea temperature and NBG rates into the widgets', function () {
    fakeInfoApis();

    $this->artisan('info:refresh')->assertSuccessful();

    $info = app(UsefulInfo::class)->data();

    expect($info['weather']['air'])->toBe('+22°')
        ->and($info['weather']['sea'])->toBe('+23°')
        ->and($info['rates'])->toBe(['USD' => '2.71', 'EUR' => '3.14', 'TRY' => '0.079'])
        ->and(app(ExchangeRates::class)->rate(Currency::USD))->toBe(2.7135);

    $this->get('/en')->assertOk()->assertSee('+22°')->assertSee('2.71');
});

it('keeps the last good values when an API is down', function () {
    Cache::forever(UsefulInfo::WEATHER_KEY, ['air' => 21.6, 'code' => 0, 'wind' => 2, 'sea' => 23, 'fetched_at' => now()->toIso8601String()]);
    Http::fake(['*' => Http::response('down', 503)]);
    $this->artisan('info:refresh')->assertFailed();

    expect(app(UsefulInfo::class)->data()['weather']['air'])->toBe('+22°');
});

it('renders the homepage before any data was fetched', function () {
    $info = app(UsefulInfo::class)->data();

    expect($info['weather'])->toBeNull()
        ->and($info['rates']['USD'])->toBe('—')
        ->and(app(ExchangeRates::class)->rate(Currency::USD))->toBe((float) config('currency.fallback_rates.USD'));

    $this->get('/')->assertOk();
});

it('uses the transport texts edited in the admin', function () {
    app(Settings::class)->put(['info.airport_km' => 28, 'info.railway_value.en' => 'Kobuleti railway station']);

    app()->setLocale('en');
    $info = app(UsefulInfo::class)->data();

    expect($info['airport']['distance'])->toContain('28')
        ->and($info['railway']['value'])->toBe('Kobuleti railway station');
});
