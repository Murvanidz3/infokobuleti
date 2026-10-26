<?php

use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\SiteFeature;
use App\Livewire\Billing\Analytics;
use App\Models\BusinessSubscription;
use App\Models\DailyStat;
use App\Models\Event;
use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use App\Services\HomepageData;
use App\Services\Insights\PriceInsight;
use App\Services\Settings;
use App\Support\Features;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

function comparable(float $priceGel, float $area = 50, ?int $locationId = null): Property
{
    return Property::factory()->published()->create([
        'deal' => PropertyDeal::Sale,
        'type' => PropertyType::Apartment,
        'price' => $priceGel,
        'currency' => 'GEL',
        'area_m2' => $area,
        'location_id' => $locationId,
    ]);
}

it('compares the price per m² with the median of similar listings', function () {
    $location = Location::factory()->create();
    foreach ([1000, 1100, 1200, 1300, 1400] as $perM2) {
        comparable($perM2 * 50, 50, $location->id);
    }

    $cheap = comparable(900 * 50, 50, $location->id);
    $insight = app(PriceInsight::class)->forProperty($cheap);

    expect($insight)->not->toBeNull()
        ->and($insight['verdict'])->toBe('below')
        ->and($insight['diff'])->toBe(25)
        ->and($insight['count'])->toBe(5)
        ->and($insight['area'])->toBe((string) $location->name);

    expect(app(PriceInsight::class)->estimate('sale', 'apartment', 1200 * 60, 'GEL', 60, $location->id)['verdict'])->toBe('fair')
        ->and(app(PriceInsight::class)->estimate('sale', 'apartment', 2000 * 60, 'GEL', 60, $location->id)['verdict'])->toBe('above');

    $this->get(localized_route('real-estate.show', [$cheap]))->assertOk()->assertSee(__('insights.price.title'));
});

it('falls back to the whole city and gives no verdict with too few comparables', function () {
    $empty = Location::factory()->create();
    foreach ([1000, 1100, 1200] as $perM2) {
        comparable($perM2 * 50);
    }

    expect(app(PriceInsight::class)->estimate('sale', 'apartment', 60000, 'GEL', 50, $empty->id))->toBeNull();

    comparable(1300 * 50);
    comparable(1400 * 50);
    Cache::flush(); // comparables are cached for an hour

    expect(app(PriceInsight::class)->estimate('sale', 'apartment', 60000, 'GEL', 50, $empty->id)['area'])->toBe(__('insights.price.city'));

    Features::set(SiteFeature::PriceInsight, false);
    expect(app(PriceInsight::class)->estimate('sale', 'apartment', 60000, 'GEL', 50, null))->toBeNull();
});

it('orders the homepage by season, with the admin override', function () {
    $this->travelTo(now()->setDate(2026, 7, 15));
    expect(app(HomepageData::class)->sections()[0])->toBe('hotels');

    $this->travelTo(now()->setDate(2026, 11, 15));
    expect(app(HomepageData::class)->sections()[0])->toBe('real-estate')
        ->and(array_column(app(HomepageData::class)->categories(), 'icon')[0])->toBe('building');

    app(Settings::class)->put(['homepage.season' => 'summer']);
    expect(app(HomepageData::class)->sections()[0])->toBe('hotels');

    Features::set(SiteFeature::SeasonalHome, false);
    expect(app(HomepageData::class)->sections())->toBe(['hotels', 'real-estate', 'market', 'guide', 'info', 'news']);
});

it('shows upcoming events on the seasonal homepage', function () {
    $event = Event::factory()->published()->create(['starts_at' => now()->addDays(3)]);

    $this->get('/')->assertOk()->assertSee(__('home.events.title'))->assertSee($event->getTranslation('title', 'ka'));
});

it('adds conversion and the best day to bump to package analytics', function () {
    $user = User::factory()->create();
    BusinessSubscription::factory()->for($user)->create();
    $listing = Property::factory()->published()->for($user)->create();

    foreach (range(0, 27) as $day) {
        $date = today()->subDays($day);
        DailyStat::factory()->create(['statable_type' => 'property', 'statable_id' => $listing->id, 'date' => $date, 'views' => $date->isSaturday() ? 50 : 10, 'phone_reveals' => 1, 'messages' => 0]);
    }

    Livewire::actingAs($user)->test(Analytics::class)
        ->assertViewHas('bestDay', today()->startOfWeek()->addDays(5)->locale('ka')->translatedFormat('l'))
        ->assertViewHas('conversion', fn (?float $value) => $value !== null && $value > 0)
        ->assertSee(__('insights.business.best_day'));
});
