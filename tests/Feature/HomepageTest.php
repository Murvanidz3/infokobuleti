<?php

use App\Enums\PropertyDeal;
use App\Models\Article;
use App\Models\Classified;
use App\Models\Hotel;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Favorites;
use App\Services\HomepageData;
use App\Services\Settings;

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

it('renders favorite buttons with the visitor\'s saved state', function () {
    $user = User::factory()->create();
    $hotels = Hotel::factory()->count(3)->create();
    Property::factory()->count(2)->create();
    app(Favorites::class)->toggle($user, $hotels[1]);

    $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

    expect(preg_match_all('/data-favorite="(hotel|property):\\d+"/', $html))->toBe(5)
        ->and(preg_match_all('/data-favorite="hotel:'.$hotels[1]->id.'"\\s+aria-pressed="true"/', $html))->toBe(1)
        ->and(preg_match_all('/data-favorite="[a-z_]+:\\d+"\\s+aria-pressed="true"/', $html))->toBe(1);
});

it('tags property cards with their deal for the segmented filter', function () {
    Property::factory()->count(2)->create(['deal' => PropertyDeal::Sale]);
    Property::factory()->create(['deal' => PropertyDeal::Rent]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(preg_match_all('/data-segment="sale"/', $html))->toBe(2)
        ->and(preg_match_all('/data-segment="rent"/', $html))->toBe(1);
});

it('shows published listings from every module and nothing hidden', function () {
    $hotel = Hotel::factory()->create(['title' => ['ka' => 'Sunrise Inn', 'en' => 'Sunrise Inn']]);
    $draft = Hotel::factory()->draft()->create(['title' => ['ka' => 'Draft Palace', 'en' => 'Draft Palace']]);
    $expired = Hotel::factory()->create(['title' => ['ka' => 'Expired Lodge', 'en' => 'Expired Lodge'], 'expires_at' => now()->subDay()]);
    $property = Property::factory()->create(['title' => ['ka' => 'ბინა ზღვის ხედით', 'en' => 'Flat with a sea view']]);
    $classified = Classified::factory()->create(['title' => ['ka' => 'ველოსიპედი', 'en' => 'Road bike']]);
    $vacancy = Vacancy::factory()->create(['title' => ['ka' => 'მიმტანი', 'en' => 'Waiter']]);
    $article = Article::factory()->create(['title' => ['ka' => 'ახალი ბულვარი', 'en' => 'New boulevard']]);

    $this->get('/en')
        ->assertOk()
        ->assertSee('Sunrise Inn')
        ->assertSee('Flat with a sea view')
        ->assertSee('Road bike')
        ->assertSee('Waiter')
        ->assertSee('New boulevard')
        ->assertSee(localized_route('hotels.show', [$hotel], 'en'), false)
        ->assertDontSee('Draft Palace')
        ->assertDontSee('Expired Lodge');
});

it('puts admin-picked hotels first, then promoted ones', function () {
    $plain = Hotel::factory()->create(['title' => ['ka' => 'Plain Hotel', 'en' => 'Plain Hotel'], 'promotion_level' => 0]);
    $vip = Hotel::factory()->create(['title' => ['ka' => 'Vip Hotel', 'en' => 'Vip Hotel']]);
    $vip->forceFill(['promotion_level' => 2])->save();
    $picked = Hotel::factory()->create(['title' => ['ka' => 'Picked Hotel', 'en' => 'Picked Hotel']]);
    app(Settings::class)->put(['homepage.featured_hotels' => [$picked->id]]);

    $names = array_column(app(HomepageData::class)->hotels(), 'name');

    expect($names)->toBe(['Picked Hotel', 'Vip Hotel', 'Plain Hotel']);
});

it('counts listings per category tile and caches the blocks until a listing changes', function () {
    Hotel::factory()->count(2)->create();
    Hotel::factory()->pending()->create();

    // The tile order follows the season, so the hotels tile is found by its icon.
    $hotels = fn (): array => collect(app(HomepageData::class)->categories())->firstWhere('icon', 'bed');
    expect($hotels()['count'])->toContain('2');

    Hotel::factory()->create();

    expect($hotels()['count'])->toContain('3');
});
