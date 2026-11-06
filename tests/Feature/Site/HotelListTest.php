<?php

use App\Enums\AmenityScope;
use App\Enums\HotelType;
use App\Enums\ListingModule;
use App\Enums\ListingStatus;
use App\Livewire\Listings\HotelIndex;
use App\Models\Amenity;
use App\Models\Hotel;
use App\Models\RoomAvailability;
use App\Models\RoomType;
use App\Models\SavedSearch;
use App\Models\User;
use Livewire\Livewire;

function hotelNames(mixed $component): array
{
    return collect($component->viewData('items'))->pluck('name')->sort()->values()->all();
}

function namedHotel(string $name, array $attributes = []): Hotel
{
    return Hotel::factory()->create(['title' => ['ka' => $name, 'en' => $name], ...$attributes]);
}

it('renders the hotels page in every locale', function (string $url) {
    namedHotel('Sea Breeze');

    $this->get($url)->assertOk()->assertSee('Sea Breeze');
})->with(['/hotels', '/en/hotels', '/ru/hotels']);

it('never lists hidden statuses or expired hotels', function () {
    namedHotel('Visible');
    namedHotel('Draft', ['status' => ListingStatus::Draft, 'published_at' => null]);
    namedHotel('Pending', ['status' => ListingStatus::Pending, 'published_at' => null]);
    namedHotel('Rejected', ['status' => ListingStatus::Rejected]);
    namedHotel('Archived', ['status' => ListingStatus::Archived]);
    namedHotel('Expired', ['expires_at' => now()->subMinute()]);
    namedHotel('Trashed')->delete();

    $component = Livewire::test(HotelIndex::class);

    expect(hotelNames($component))->toBe(['Visible'])
        ->and($component->viewData('total'))->toBe(1);
});

it('filters by type, stars, price, distance to the sea and rating', function () {
    namedHotel('A', ['type' => HotelType::Hotel, 'stars' => 4, 'distance_to_sea_m' => 80, 'rating_avg' => 9.1]);
    namedHotel('B', ['type' => HotelType::Hotel, 'stars' => 3, 'distance_to_sea_m' => 400, 'rating_avg' => 8.2]);
    namedHotel('C', ['type' => HotelType::Guesthouse, 'stars' => null, 'distance_to_sea_m' => 50, 'rating_avg' => 7.4]);
    Hotel::query()->where('title->en', 'A')->toBase()->update(['price_from' => 200]);
    Hotel::query()->where('title->en', 'B')->toBase()->update(['price_from' => 90]);
    Hotel::query()->where('title->en', 'C')->toBase()->update(['price_from' => 60]);

    expect(hotelNames(Livewire::test(HotelIndex::class)->set('type', ['hotel'])))->toBe(['A', 'B'])
        ->and(hotelNames(Livewire::test(HotelIndex::class)->set('stars', ['4'])))->toBe(['A'])
        ->and(hotelNames(Livewire::test(HotelIndex::class)->set('price_max', '100')))->toBe(['B', 'C'])
        ->and(hotelNames(Livewire::test(HotelIndex::class)->set('price_min', '80')->set('price_max', '150')))->toBe(['B'])
        ->and(hotelNames(Livewire::test(HotelIndex::class)->set('sea', '100')))->toBe(['A', 'C'])
        ->and(hotelNames(Livewire::test(HotelIndex::class)->set('rating', '8')))->toBe(['A', 'B']);
});

it('filters by amenities, all of them at once', function () {
    $pool = Amenity::factory()->create(['scope' => AmenityScope::Hotel, 'slug' => 'pool']);
    $parking = Amenity::factory()->create(['scope' => AmenityScope::Hotel, 'slug' => 'parking']);
    namedHotel('Both')->amenities()->attach([$pool->id, $parking->id]);
    namedHotel('Pool only')->amenities()->attach($pool->id);
    namedHotel('None');

    expect(hotelNames(Livewire::test(HotelIndex::class)->set('amenities', ['pool'])))->toBe(['Both', 'Pool only'])
        ->and(hotelNames(Livewire::test(HotelIndex::class)->set('amenities', ['pool', 'parking'])))->toBe(['Both']);
});

it('finds hotels with a free, large enough room for the dates', function () {
    $checkIn = now()->addDays(10)->startOfDay();

    $free = namedHotel('Free', ['season_start_month' => null, 'season_end_month' => null]);
    RoomType::factory()->for($free)->create(['capacity_adults' => 2, 'capacity_children' => 1]);

    $full = namedHotel('Closed nights', ['season_start_month' => null, 'season_end_month' => null]);
    $room = RoomType::factory()->for($full)->create(['capacity_adults' => 4]);
    RoomAvailability::query()->create(['room_type_id' => $room->id, 'date' => $checkIn->copy()->addDay()->toDateString(), 'is_closed' => true]);

    $small = namedHotel('Too small', ['season_start_month' => null, 'season_end_month' => null]);
    RoomType::factory()->for($small)->create(['capacity_adults' => 1, 'capacity_children' => 0]);

    $component = Livewire::test(HotelIndex::class)
        ->set('check_in', $checkIn->toDateString())
        ->set('check_out', $checkIn->copy()->addDays(3)->toDateString())
        ->set('guests', '3');

    expect(hotelNames($component))->toBe(['Free']);
});

it('hides seasonal hotels outside their season', function () {
    $winter = now()->month(1)->day(15)->addYear()->startOfDay();
    namedHotel('Summer only', ['season_start_month' => 5, 'season_end_month' => 10]);
    namedHotel('All year', ['season_start_month' => null, 'season_end_month' => null]);
    namedHotel('Winter wrap', ['season_start_month' => 11, 'season_end_month' => 2]);

    $component = Livewire::test(HotelIndex::class)->set('check_in', $winter->toDateString());

    expect(hotelNames($component))->toBe(['All year', 'Winter wrap']);
});

it('sorts by price, sea distance and rating', function () {
    namedHotel('Cheap far', ['distance_to_sea_m' => 900, 'rating_avg' => 7.0]);
    namedHotel('Pricey close', ['distance_to_sea_m' => 20, 'rating_avg' => 9.5]);
    Hotel::query()->where('title->en', 'Cheap far')->toBase()->update(['price_from' => 40]);
    Hotel::query()->where('title->en', 'Pricey close')->toBase()->update(['price_from' => 300]);

    $order = fn (string $sort): array => collect(Livewire::test(HotelIndex::class)->set('sort', $sort)->viewData('items'))->pluck('name')->all();

    expect($order('price_asc'))->toBe(['Cheap far', 'Pricey close'])
        ->and($order('price_desc'))->toBe(['Pricey close', 'Cheap far'])
        ->and($order('sea'))->toBe(['Pricey close', 'Cheap far'])
        ->and($order('rating'))->toBe(['Pricey close', 'Cheap far']);
});

it('keeps filters in the URL so links can be shared', function () {
    namedHotel('Guesthouse', ['type' => HotelType::Guesthouse]);
    namedHotel('Grand Stay', ['type' => HotelType::Hotel, 'stars' => 3]);

    $this->get('/en/hotels?type[]=guesthouse&sort=price_asc')
        ->assertOk()
        ->assertSee('Guesthouse')
        ->assertDontSee('Grand Stay');

    Livewire::withQueryParams(['type' => ['hotel'], 'view' => 'list'])
        ->test(HotelIndex::class)
        ->assertSet('type', ['hotel'])
        ->assertSet('display', 'list')
        ->assertViewHas('total', 1);
});

it('survives hand-edited query strings', function () {
    namedHotel('Guesthouse', ['type' => HotelType::Guesthouse]);

    Livewire::withQueryParams(['type' => 'guesthouse', 'view' => 'nonsense', 'sort' => 'bogus', 'check_in' => 'yesterday'])
        ->test(HotelIndex::class)
        ->assertSet('type', ['guesthouse'])
        ->assertSet('display', 'grid')
        ->assertSet('sort', 'recommended')
        ->assertViewHas('total', 1);
});

it('shows removable chips and clears filters', function () {
    namedHotel('One', ['type' => HotelType::Hotel, 'stars' => 5]);

    Livewire::test(HotelIndex::class)
        ->set('type', ['hotel', 'cottage'])
        ->set('stars', ['5'])
        ->assertViewHas('chips', fn (array $chips) => count($chips) === 3)
        ->call('removeFilter', 'type', 'cottage')
        ->assertSet('type', ['hotel'])
        ->call('resetFilters')
        ->assertSet('type', [])
        ->assertSet('stars', []);
});

it('saves the search for signed-in visitors and asks guests to sign in', function () {
    Livewire::test(HotelIndex::class)
        ->set('type', ['hotel'])
        ->call('saveSearch')
        ->assertDispatched('require-login');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(HotelIndex::class)
        ->set('type', ['hotel'])
        ->set('sea', '300')
        ->call('saveSearch')
        ->assertDispatched('toast');

    $search = SavedSearch::query()->sole();

    expect($search->user_id)->toBe($user->id)
        ->and($search->module)->toBe(ListingModule::Hotels)
        ->and($search->filters)->toEqualCanonicalizing(['type' => ['hotel'], 'sea' => '300']);
});

it('pages through results and loads more on scroll', function () {
    Hotel::factory()->count(15)->create();

    $component = Livewire::test(HotelIndex::class);
    expect($component->viewData('items'))->toHaveCount(12)
        ->and($component->viewData('hasMore'))->toBeTrue();

    $component->call('loadMore');
    expect($component->viewData('items'))->toHaveCount(15)
        ->and($component->viewData('hasMore'))->toBeFalse();

    expect(Livewire::withQueryParams(['page' => 2])->test(HotelIndex::class)->viewData('items'))->toHaveCount(3);
});

it('returns map pins for the map view and searches the visible area', function () {
    namedHotel('Inside', ['lat' => 41.82, 'lng' => 41.78]);
    namedHotel('Outside', ['lat' => 41.60, 'lng' => 41.60]);

    $component = Livewire::test(HotelIndex::class)->set('display', 'map');

    expect($component->viewData('markers'))->toHaveCount(2);

    $component->call('searchArea', 41.80, 41.75, 41.85, 41.80)
        ->assertSet('bounds', '41.8,41.75,41.85,41.8')
        ->assertDispatched('markers-updated');

    expect(collect($component->viewData('markers'))->pluck('title')->all())->toBe(['Inside']);
});
