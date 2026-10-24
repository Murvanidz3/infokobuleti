<?php

use App\Services\PriceFormatter;

beforeEach(function () {
    $this->prices = new PriceFormatter;
});

it('puts the currency symbol first', function () {
    expect($this->prices->format(220, 'GEL', 'ka'))->toBe('₾220')
        ->and($this->prices->format(95, 'EUR', 'en'))->toBe('€95');
});

it('groups thousands per locale', function () {
    expect($this->prices->format(58000, 'USD', 'ka'))->toBe("\$58\u{00A0}000")
        ->and($this->prices->format(58000, 'USD', 'en'))->toBe('$58,000');
});

it('formats ranges with a single symbol', function () {
    expect($this->prices->range(1500, 2000, 'GEL', 'en'))->toBe('₾1,500–2,000');
});

it('falls back to the currency code for unknown currencies', function () {
    expect($this->prices->format(10, 'TRY', 'en'))->toBe('TRY 10');
});

it('uses the application locale by default', function () {
    app()->setLocale('en');

    expect($this->prices->number(1248))->toBe('1,248');
});
