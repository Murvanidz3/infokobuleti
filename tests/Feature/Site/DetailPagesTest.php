<?php

use App\Enums\ListingStatus;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Classified;
use App\Models\DailyStat;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\ListingStats;

dataset('modules', [
    'hotel' => [Hotel::class, 'hotels.show'],
    'property' => [Property::class, 'real-estate.show'],
    'classified' => [Classified::class, 'classifieds.show'],
    'vacancy' => [Vacancy::class, 'jobs.show'],
    'place' => [Place::class, 'guide.show'],
    'event' => [Event::class, 'events.show'],
    'article' => [Article::class, 'news.show'],
]);

it('shows a published item in every language', function (string $class, string $route) {
    $item = $class::factory()->create(['title' => ['ka' => 'ქართული სათაური', 'en' => 'English title', 'ru' => 'Русский заголовок']]);

    $this->get(localized_route($route, [$item], 'ka'))->assertOk()->assertSee('ქართული სათაური')->assertSee('"@type":"BreadcrumbList"', false);
    $this->get(localized_route($route, [$item], 'en'))->assertOk()->assertSee('English title');
    $this->get(localized_route($route, [$item], 'ru'))->assertOk()->assertSee('Русский заголовок');
})->with('modules');

it('hides unpublished items from visitors but lets the owner and staff preview them', function (string $class, string $route) {
    $item = $class::factory()->pending()->create();
    $url = localized_route($route, [$item]);

    $this->get($url)->assertNotFound();
    $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
    $this->actingAs(User::query()->find($item->user_id))->get($url)->assertOk()->assertSee(__('site.preview', ['status' => ListingStatus::Pending->getLabel()]));
    [$staff] = staffMember(App\Enums\Role::Moderator);
    $this->actingAs($staff)->get($url)->assertOk();
})->with('modules');

it('returns 404 for expired, deleted and unknown items', function () {
    $expired = Hotel::factory()->create(['expires_at' => now()->subDay()]);
    $deleted = Hotel::factory()->create();
    $deleted->delete();

    $this->get(localized_route('hotels.show', [$expired]))->assertNotFound();
    $this->get(localized_route('hotels.show', [$deleted]))->assertNotFound();
    $this->get('/hotels/no-such-hotel')->assertNotFound()->assertSee(__('site.errors.404.title'));
    $this->get('/en/hotels/no-such-hotel')->assertNotFound()->assertSee('Page not found');
});

it('counts a view once per visitor session, never for the owner', function () {
    $hotel = Hotel::factory()->create(['views_count' => 0]);
    $url = localized_route('hotels.show', [$hotel]);

    $this->get($url);
    $this->get($url);

    expect($hotel->fresh()->views_count)->toBe(1)
        ->and(DailyStat::query()->where('statable_type', 'hotel')->where('statable_id', $hotel->id)->value('views'))->toBe(1);

    $this->actingAs(User::query()->find($hotel->user_id))->get($url);

    expect($hotel->fresh()->views_count)->toBe(1);
});

it('shows hotel rooms, the availability calendar and the booking form', function () {
    $hotel = Hotel::factory()->create(['season_start_month' => null, 'season_end_month' => null]);
    RoomType::factory()->for($hotel)->create(['name' => ['ka' => 'ორადგილიანი ნომერი', 'en' => 'Double room'], 'base_price' => 150]);

    $this->get(localized_route('hotels.show', [$hotel], 'en'))
        ->assertOk()
        ->assertSee('Double room')
        ->assertSee('₾150')
        ->assertSee(__('site.hotels.availability', [], 'en'))
        ->assertSee('name="check_in"', false)
        ->assertSee(localized_route('hotels.booking', [$hotel], 'en'), false);
});

it('never puts phone numbers in the page source', function () {
    $hotel = Hotel::factory()->create(['contact_phone' => '+995599887766']);

    $this->get(localized_route('hotels.show', [$hotel]))
        ->assertOk()
        ->assertDontSee('599887766')
        ->assertDontSee('599 88 77 66')
        ->assertSee(__('site.owner.show_phone'));
});

it('shows published reviews only', function () {
    $hotel = Hotel::factory()->create();
    Review::factory()->for($hotel, 'reviewable')->create(['body' => 'Lovely stay by the sea']);
    Review::factory()->for($hotel, 'reviewable')->pending()->create(['body' => 'Not yet moderated']);

    $this->get(localized_route('hotels.show', [$hotel]))
        ->assertSee('Lovely stay by the sea')
        ->assertDontSee('Not yet moderated');
});

it('shows a business profile with its published listings and nothing hidden', function () {
    $business = BusinessProfile::factory()->create(['name' => 'Sea Group']);
    Hotel::factory()->create(['business_profile_id' => $business->id, 'user_id' => $business->user_id, 'title' => ['ka' => 'Sea Hotel', 'en' => 'Sea Hotel']]);
    Hotel::factory()->draft()->create(['business_profile_id' => $business->id, 'user_id' => $business->user_id, 'title' => ['ka' => 'Secret Hotel', 'en' => 'Secret Hotel']]);

    $this->get(localized_route('business.show', [$business]))
        ->assertOk()
        ->assertSee('Sea Group')
        ->assertSee('Sea Hotel')
        ->assertDontSee('Secret Hotel');
});

it('shows guide, events and news index pages', function () {
    Place::factory()->create(['title' => ['ka' => 'ცენტრალური პლაჟი', 'en' => 'Central beach'], 'type' => 'beach']);
    Event::factory()->create(['title' => ['ka' => 'ჯაზის საღამო', 'en' => 'Jazz night'], 'starts_at' => now()->addDays(3), 'ends_at' => null]);
    Event::factory()->create(['title' => ['ka' => 'Old concert', 'en' => 'Old concert'], 'starts_at' => now()->subDays(10), 'ends_at' => now()->subDays(10)]);
    Article::factory()->create(['title' => ['ka' => 'ახალი ბულვარი', 'en' => 'New boulevard']]);

    $this->get('/en/guide')->assertOk()->assertSee('Central beach');
    $this->get('/en/guide?type=beach')->assertOk()->assertSee('Central beach');
    $this->get('/en/guide?type=museum')->assertOk()->assertDontSee('Central beach');
    $this->get('/en/events')->assertOk()->assertSee('Jazz night')->assertDontSee('Old concert');
    $this->get('/en/events?view=calendar&month='.now()->addDays(3)->format('Y-m'))->assertOk()->assertSee('Jazz night');
    $this->get('/en/news')->assertOk()->assertSee('New boulevard');
});

it('renders static pages and hides unpublished ones', function () {
    App\Models\Page::query()->create(['slug' => 'about', 'title' => ['ka' => 'ჩვენს შესახებ', 'en' => 'About us'], 'body' => ['en' => '<p>Hello <script>alert(1)</script><strong>Kobuleti</strong></p>'], 'is_published' => true]);
    App\Models\Page::query()->create(['slug' => 'draft', 'title' => ['en' => 'Draft page'], 'is_published' => false]);

    $this->get('/en/info/about')->assertOk()->assertSee('About us')->assertSee('<strong>Kobuleti</strong>', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->get('/en/info/draft')->assertNotFound();
});

it('records statistics with an atomic upsert', function () {
    $hotel = Hotel::factory()->create();
    $stats = app(ListingStats::class);

    $stats->record($hotel, 'phone_reveals');
    $stats->record($hotel, 'phone_reveals');
    $stats->record($hotel, 'unknown_metric');

    expect(DailyStat::query()->where('statable_id', $hotel->id)->value('phone_reveals'))->toBe(2);
});
