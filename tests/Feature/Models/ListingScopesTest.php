<?php

use App\Models\Event;
use App\Models\Place;

it('finds places inside a map viewport', function () {
    $inside = Place::factory()->create(['lat' => 41.8200, 'lng' => 41.7760]);
    Place::factory()->create(['lat' => 41.7200, 'lng' => 41.7300]);

    expect(Place::query()->withinBounds(41.80, 41.76, 41.84, 41.79)->pluck('id')->all())->toBe([$inside->id]);
});

it('sorts places by distance and reports it in metres', function () {
    $far = Place::factory()->create(['lat' => 41.7667, 'lng' => 41.7517]);
    $near = Place::factory()->create(['lat' => 41.8210, 'lng' => 41.7760]);

    $results = Place::query()->nearest(41.8200, 41.7753)->get();

    expect($results->pluck('id')->all())->toBe([$near->id, $far->id])
        ->and((float) $results->first()->distance_m)->toBeLessThan(200)
        ->and((float) $results->last()->distance_m)->toBeGreaterThan(6000);
});

it('lists upcoming events soonest first and skips finished ones', function () {
    Event::factory()->create(['starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHours(2)]);
    $later = Event::factory()->create(['starts_at' => now()->addDays(10), 'ends_at' => null]);
    $soon = Event::factory()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3)]);

    expect(Event::query()->upcoming()->pluck('id')->all())->toBe([$soon->id, $later->id]);
});
