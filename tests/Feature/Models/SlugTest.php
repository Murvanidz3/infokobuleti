<?php

use App\Enums\CategoryModule;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Hotel;
use App\Models\Location;

it('transliterates the Georgian title into a latin slug', function () {
    $hotel = Hotel::factory()->create(['title' => ['ka' => 'სასტუმრო „ოქროს ქვიშა“', 'en' => 'Golden Sand Hotel']]);

    expect($hotel->slug)->toBe('sastumro-oqros-qvisha');
});

it('falls back to the English title when there is no Georgian one', function () {
    $hotel = Hotel::factory()->create(['title' => ['en' => 'Blue Boulevard Boutique Hotel']]);

    expect($hotel->slug)->toBe('blue-boulevard-boutique-hotel');
});

it('adds a numeric suffix when the slug is taken', function () {
    $first = Hotel::factory()->create(['title' => ['ka' => 'ზღვის ბრიზი']]);
    $second = Hotel::factory()->create(['title' => ['ka' => 'ზღვის ბრიზი']]);
    $third = Hotel::factory()->create(['title' => ['ka' => 'ზღვის ბრიზი']]);

    expect([$first->slug, $second->slug, $third->slug])->toBe(['zghvis-brizi', 'zghvis-brizi-2', 'zghvis-brizi-3']);
});

it('does not reuse the slug of a soft-deleted listing', function () {
    Hotel::factory()->create(['title' => ['ka' => 'ციტრუსი']])->delete();

    expect(Hotel::factory()->create(['title' => ['ka' => 'ციტრუსი']])->slug)->toBe('tsitrusi-2');
});

it('keeps the slug when the title changes', function () {
    $hotel = Hotel::factory()->create(['title' => ['ka' => 'კოლხეთი']]);

    $hotel->update(['title' => ['ka' => 'კოლხეთი რეზორტი']]);

    expect($hotel->fresh()->slug)->toBe('kolkheti');
});

it('keeps an explicitly given slug', function () {
    expect(Location::factory()->create(['slug' => 'tsikhisdziri'])->slug)->toBe('tsikhisdziri');
});

it('scopes category slugs to their module', function () {
    $classifieds = Category::factory()->module(CategoryModule::Classifieds)->create(['name' => ['ka' => 'ტრანსპორტი']]);
    $vacancies = Category::factory()->module(CategoryModule::Vacancies)->create(['name' => ['ka' => 'ტრანსპორტი']]);

    expect($classifieds->slug)->toBe('transporti')
        ->and($vacancies->slug)->toBe('transporti');
});

it('builds business profile slugs from the plain name', function () {
    expect(BusinessProfile::factory()->create(['name' => 'Zghvis Sakhli Agency'])->slug)->toBe('zghvis-sakhli-agency');
});

it('resolves route model binding by slug', function () {
    expect((new Hotel)->getRouteKeyName())->toBe('slug');
});
