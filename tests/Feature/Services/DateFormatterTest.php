<?php

use App\Services\DateFormatter;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-24 12:00', 'Asia/Tbilisi'));
    $this->dates = new DateFormatter;
});

afterEach(function () {
    Carbon::setTestNow();
});

it('formats a weekday heading with the nominative weekday', function (string $locale, string $expected) {
    app()->setLocale($locale);

    expect($this->dates->weekdayDate(Carbon::parse('2026-10-24')))->toBe($expected);
})->with([
    ['ka', 'შაბათი, 24 ოქტომბერი'],
    ['en', 'Saturday, 24 October'],
    ['ru', 'Суббота, 24 октября'],
]);

it('formats full dates per locale', function (string $locale, string $expected) {
    app()->setLocale($locale);

    expect($this->dates->date(Carbon::parse('2026-11-02')))->toBe($expected);
})->with([
    ['ka', '2 ნოემბერი, 2026'],
    ['en', '2 November 2026'],
    ['ru', '2 ноября 2026'],
]);

it('uses short relative times', function () {
    app()->setLocale('ka');

    expect($this->dates->relative(now()->subMinutes(5)))->toBe('5 წთ წინ')
        ->and($this->dates->relative(now()->subHours(2)))->toBe('2 სთ წინ')
        ->and($this->dates->relative(now()->subDay()))->toBe('გუშინ')
        ->and($this->dates->relative(now()->subDays(3)))->toBe('3 დღის წინ');
});

it('uses calendar days for relative days', function () {
    app()->setLocale('ka');

    expect($this->dates->relativeDay(now()->setTime(8, 0)))->toBe('დღეს')
        ->and($this->dates->relativeDay(now()->subDay()->setTime(23, 0)))->toBe('გუშინ');
});

it('pluralizes relative days in English and Russian', function () {
    app()->setLocale('en');
    expect($this->dates->relativeDay(now()->subDays(2)))->toBe('2 days ago');

    app()->setLocale('ru');
    expect($this->dates->relativeDay(now()->subDays(2)))->toBe('2 дня назад')
        ->and($this->dates->relativeDay(now()->subDays(5)))->toBe('5 дней назад');
});

it('formats times as 24-hour clock', function () {
    expect($this->dates->time(Carbon::parse('2026-10-24 10:30')))->toBe('10:30');
});
