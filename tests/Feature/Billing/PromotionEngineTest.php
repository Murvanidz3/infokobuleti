<?php

use App\Actions\Billing\ActivatePurchase;
use App\Actions\Billing\PayFromBalance;
use App\Enums\ListingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PromotionType;
use App\Enums\WalletEntryType;
use App\Models\BusinessProfile;
use App\Models\BusinessSubscription;
use App\Models\Classified;
use App\Models\Hotel;
use App\Models\PricePlan;
use App\Models\Promotion;
use App\Models\Property;
use App\Models\User;
use App\Notifications\PromotionExpiring;
use App\Services\Billing\OrderBuilder;
use App\Services\Billing\Wallet;
use App\Services\HomepageData;
use App\Support\Listings\CardPresenter;
use Carbon\CarbonInterface;
use Database\Seeders\PricePlanSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function promote(Property|Hotel|Classified $listing, PromotionType $type, ?CarbonInterface $startsAt = null, ?CarbonInterface $endsAt = null): Promotion
{
    return Promotion::factory()->create([
        'promotable_type' => $listing->getMorphClass(),
        'promotable_id' => $listing->id,
        'user_id' => $listing->user_id,
        'type' => $type,
        'starts_at' => $startsAt ?? now()->subHour(),
        'ends_at' => $type === PromotionType::Bump ? null : ($endsAt ?? now()->addDays(3)),
    ]);
}

it('orders Super VIP, then VIP, then the last bump, then the publication date', function () {
    $old = Property::factory()->published()->create(['published_at' => now()->subDays(10)]);
    $newest = Property::factory()->published()->create(['published_at' => now()->subHour()]);
    $bumped = Property::factory()->published()->create(['published_at' => now()->subDays(20)]);
    $vip = Property::factory()->published()->create(['published_at' => now()->subDays(30)]);
    $super = Property::factory()->published()->create(['published_at' => now()->subDays(40)]);

    promote($bumped, PromotionType::Bump, now()->subMinutes(5));
    promote($vip, PromotionType::Vip);
    promote($super, PromotionType::SuperVip);

    expect(Property::query()->orderByPromotion()->pluck('id')->all())
        ->toBe([$super->id, $vip->id, $bumped->id, $newest->id, $old->id]);
});

it('gives every VIP its turn on the homepage while Super VIP stays above', function () {
    $super = Property::factory()->published()->create();
    $vips = Property::factory()->published()->count(4)->create()->each(fn (Property $property) => promote($property, PromotionType::Vip));
    promote($super, PromotionType::SuperVip);

    $firstVip = collect(range(1, 40))
        ->map(fn (int $seed) => Property::query()->orderByPromotionRotating($seed)->pluck('id')->all())
        ->each(fn (array $order) => expect($order[0])->toBe($super->id))
        ->map(fn (array $order) => $order[1])
        ->unique();

    expect($firstVip->count())->toBe(4)
        ->and($firstVip->sort()->values()->all())->toBe($vips->pluck('id')->sort()->values()->all());
});

it('puts paid homepage slots right after the admin-picked hotels', function () {
    $regular = Hotel::factory()->published()->create(['published_at' => now()]);
    $featured = Hotel::factory()->published()->create(['published_at' => now()->subYear()]);
    promote($featured, PromotionType::FeaturedHome);

    $cards = app(HomepageData::class)->hotels();

    expect($cards[0]['url'])->toBe(localized_route('hotels.show', [$featured]))
        ->and($cards[1]['url'])->toBe(localized_route('hotels.show', [$regular]));
});

it('extends a second purchase of the same promotion from the current end', function () {
    $this->seed(PricePlanSeeder::class);
    $user = User::factory()->create(['balance' => 0]);
    $listing = Property::factory()->published()->for($user)->create();
    $plan = PricePlan::query()->where('code', 'vip-7')->firstOrFail();
    app(Wallet::class)->credit($user, 20, WalletEntryType::Adjustment);

    foreach ([1, 2] as $time) {
        $order = app(OrderBuilder::class)->forListing($user, $plan, $listing, PaymentMethod::Balance);
        app(PayFromBalance::class)->handle($order);
    }

    expect($listing->fresh()->promoted_until->isSameDay(now()->addDays(14)))->toBeTrue()
        ->and(Promotion::query()->count())->toBe(2);
});

it('drops the VIP level once the promotion has ended', function () {
    $listing = Property::factory()->published()->create();
    promote($listing, PromotionType::Vip, now()->subDays(3), now()->addHour());
    expect($listing->fresh()->promotion_level)->toBe(1);

    $this->travel(2)->hours();
    $this->artisan('promotions:run')->assertSuccessful();

    expect($listing->fresh())->promotion_level->toBe(0)->promoted_until->toBeNull();
});

it('reminds the owner one day before a promotion ends, once, unless it was extended', function () {
    Notification::fake();
    $listing = Property::factory()->published()->create();
    $extended = Property::factory()->published()->create();
    promote($listing, PromotionType::Vip, now()->subDays(6), now()->addHours(20));
    promote($extended, PromotionType::SuperVip, now()->subDays(6), now()->addHours(20));
    promote($extended, PromotionType::SuperVip, now()->subHour(), now()->addDays(7));
    promote(Property::factory()->published()->create(), PromotionType::Vip, now()->subDay(), now()->addDays(5));

    $this->artisan('promotions:run')->assertSuccessful();
    $this->artisan('promotions:run')->assertSuccessful();

    Notification::assertSentToTimes($listing->user, PromotionExpiring::class, 1);
    Notification::assertNotSentTo($extended->user, PromotionExpiring::class);
    Notification::assertCount(1);
});

it('bumps auto-bump listings when due and keeps the schedule', function () {
    $listing = Property::factory()->published()->create(['published_at' => now()->subWeek()]);
    $other = Property::factory()->published()->create(['published_at' => now()->subDay()]);
    $auto = promote($listing, PromotionType::AutoBump, now()->subDays(2), now()->addDays(5));
    $auto->forceFill(['next_run_at' => now()->subMinute()])->save();

    $this->artisan('promotions:run')->assertSuccessful();

    expect(Property::query()->orderByPromotion()->first()->id)->toBe($listing->id)
        ->and($auto->fresh()->next_run_at->isFuture())->toBeTrue()
        ->and(Promotion::query()->where('type', 'bump')->count())->toBe(1);

    // Not due yet: nothing happens.
    $this->artisan('promotions:run');
    expect(Promotion::query()->where('type', 'bump')->count())->toBe(1);

    // Paused listings are not bumped, but the schedule moves on.
    $listing->forceFill(['status' => ListingStatus::Paused])->save();
    $this->travel(25)->hours();
    $this->artisan('promotions:run');
    expect(Promotion::query()->where('type', 'bump')->count())->toBe(1)
        ->and($auto->fresh()->next_run_at->isFuture())->toBeTrue()
        ->and($other->id)->not->toBe($listing->id);
});

it('removes the business badge when the package ends and reminds a day before', function () {
    Notification::fake();
    $user = User::factory()->business()->create();
    $profile = BusinessProfile::factory()->for($user)->create(['verified_at' => null]);
    BusinessSubscription::factory()->for($user)->create(['starts_at' => now()->subDays(29), 'ends_at' => now()->addHours(10)]);
    ActivatePurchase::syncPackageFlag($user);

    expect($profile->fresh()->isVerified())->toBeTrue();

    $this->artisan('promotions:run');
    Notification::assertSentTo($user, PromotionExpiring::class, fn (PromotionExpiring $notification) => $notification->kind === 'business_package');

    $this->travel(11)->hours();
    $this->artisan('promotions:run');

    expect($profile->fresh())->package_until->toBeNull()
        ->and($profile->fresh()->isVerified())->toBeFalse();
});

it('marks promoted cards and shows the logo of package holders', function () {
    Storage::fake(config('media-library.disk_name'));
    $user = User::factory()->business()->create();
    $profile = BusinessProfile::factory()->for($user)->create();
    $profile->forceFill(['package_until' => now()->addMonth()])->save();
    $profile->addMedia(UploadedFile::fake()->image('logo.png', 200, 200))->toMediaCollection('logo');

    $listing = Property::factory()->published()->for($user)->create(['business_profile_id' => $profile->id]);
    promote($listing, PromotionType::SuperVip);

    $card = app(CardPresenter::class)->property(Property::query()->with(['media', 'location', 'businessProfile.media'])->findOrFail($listing->id));

    expect($card['promotion'])->toBe('super_vip')
        ->and($card['logo'])->toContain('logo');

    $this->get('/real-estate')->assertOk()->assertSee(__('enums.promotion_type.super_vip'));
});
