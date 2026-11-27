<?php

use App\Models\Place;
use App\Models\Property;
use App\Support\Database\ListingSchema;
use Illuminate\Support\Facades\DB;

it('returns the title in the current locale', function (string $locale, string $expected) {
    $hotel = Property::factory()->create(['title' => ['ka' => 'ზღვის ბრიზი', 'en' => 'Sea Breeze', 'ru' => 'Морской бриз']]);

    app()->setLocale($locale);

    expect($hotel->title)->toBe($expected);
})->with([
    ['ka', 'ზღვის ბრიზი'],
    ['en', 'Sea Breeze'],
    ['ru', 'Морской бриз'],
]);

it('falls back to English when a translation is missing', function () {
    $hotel = Property::factory()->create(['title' => ['ka' => 'მზიური', 'en' => 'Mziuri']]);

    app()->setLocale('ru');

    expect($hotel->title)->toBe('Mziuri');
});

it('stores translations as readable jsonb that SQL can query per locale', function () {
    Place::factory()->create(['title' => ['ka' => 'პეტრას ციხე', 'en' => 'Petra Fortress', 'ru' => 'Крепость Петра']]);

    $row = DB::selectOne("select title->>'ka' as ka, pg_typeof(title)::text as type from places");

    expect($row->ka)->toBe('პეტრას ციხე')
        ->and($row->type)->toBe('jsonb');
});

it('exposes all translations for the admin forms', function () {
    $hotel = Property::factory()->create(['title' => ['ka' => 'ლაზური', 'en' => 'Lazuri', 'ru' => 'Лазури']]);

    expect($hotel->getTranslations('title'))->toBe(['ka' => 'ლაზური', 'en' => 'Lazuri', 'ru' => 'Лазури']);
});

it('finds listings with the full-text expression in any language', function () {
    Place::factory()->create(['title' => ['ka' => 'პეტრას ციხე', 'en' => 'Petra Fortress', 'ru' => 'Крепость Петра']]);
    Place::factory()->create(['title' => ['ka' => 'ისპანის ჭაობები', 'en' => 'Ispani mires', 'ru' => 'Испанские болота']]);

    $vector = ListingSchema::searchVector();

    expect(Place::query()->whereRaw("{$vector} @@ plainto_tsquery('simple', ?)", ['fortress'])->count())->toBe(1)
        ->and(Place::query()->whereRaw("{$vector} @@ plainto_tsquery('simple', ?)", ['ციხე'])->count())->toBe(1);
});
