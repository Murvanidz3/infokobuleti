<?php

use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\SearchFrequency;
use App\Livewire\Account\NotificationChannels;
use App\Models\Property;
use App\Models\PushSubscription;
use App\Models\SavedSearch;
use App\Models\SavedSearchHit;
use App\Models\User;
use App\Notifications\SavedSearchAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-07-06 10:00')); // a Monday morning
    $this->user = User::factory()->create(['email' => 'alerts@example.com']);
});

function search(User $user, array $attributes = []): SavedSearch
{
    return SavedSearch::factory()->for($user)->create([
        'filters' => ['deal' => 'sale', 'type' => ['apartment']],
        'channels' => ['email'],
        'frequency' => SearchFrequency::Instant,
        'created_at' => now()->subDay(),
        ...$attributes,
    ]);
}

function apartment(array $attributes = []): Property
{
    return Property::factory()->published()->create([
        'deal' => PropertyDeal::Sale,
        'type' => PropertyType::Apartment,
        'published_at' => now()->subHour(),
        ...$attributes,
    ]);
}

it('alerts about new matching listings once', function () {
    Notification::fake();
    $search = search($this->user);
    $match = apartment();
    apartment(['deal' => PropertyDeal::Rent]);
    apartment(['published_at' => now()->subWeek()]);
    apartment(['user_id' => $this->user->id]);

    $this->artisan('alerts:run')->assertSuccessful();
    $this->artisan('alerts:run')->assertSuccessful();

    expect(SavedSearchHit::query()->pluck('listing_id')->all())->toBe([$match->id]);

    Notification::assertSentToTimes($this->user, SavedSearchAlert::class, 1);
    Notification::assertSentTo($this->user, SavedSearchAlert::class, fn (SavedSearchAlert $alert, array $channels) => $alert->search->is($search)
        && $alert->listings[0]->is($match)
        && in_array('mail', $channels, true));
});

it('sends daily alerts once a day from 9:00 and weekly ones on Mondays', function () {
    Notification::fake();
    search($this->user, ['frequency' => SearchFrequency::Daily]);
    $weekly = search(User::factory()->create(), ['frequency' => SearchFrequency::Weekly]);
    apartment();

    $this->travelTo(Carbon::parse('2026-07-07 08:00'));
    $this->artisan('alerts:run');
    Notification::assertNothingSent();

    $this->travelTo(Carbon::parse('2026-07-07 09:10'));
    $this->artisan('alerts:run');
    Notification::assertSentToTimes($this->user, SavedSearchAlert::class, 1);
    Notification::assertNotSentTo($weekly->user, SavedSearchAlert::class);

    apartment();
    $this->artisan('alerts:run');
    Notification::assertSentToTimes($this->user, SavedSearchAlert::class, 1);

    $this->travelTo(Carbon::parse('2026-07-13 09:30'));
    apartment();
    $this->artisan('alerts:run');
    Notification::assertSentTo($weekly->user, SavedSearchAlert::class);
});

it('delivers by web push and Telegram and drops expired push subscriptions', function () {
    $push = fakePush();
    $bot = fakeTelegram();
    $this->user->forceFill(['telegram_chat_id' => '4242'])->save();
    PushSubscription::query()->create(['user_id' => $this->user->id, 'endpoint' => 'https://push.example/alive', 'public_key' => 'k', 'auth_token' => 'a']);
    PushSubscription::query()->create(['user_id' => $this->user->id, 'endpoint' => 'https://push.example/gone', 'public_key' => 'k', 'auth_token' => 'a']);
    $push->gone = ['https://push.example/gone'];

    search($this->user, ['channels' => ['push', 'telegram']]);
    $listing = apartment();

    $this->artisan('alerts:run');

    expect($push->sent)->toHaveCount(1)
        ->and($push->sent[0]['payload']['url'])->toContain((string) $listing->slug)
        ->and(PushSubscription::query()->pluck('endpoint')->all())->toBe(['https://push.example/alive'])
        ->and($bot->sentTo('4242'))->toHaveCount(1)
        ->and($bot->sentTo('4242')[0]['html'])->toContain(e($listing->getTranslation('title', 'ka')));
});

it('stores and removes this browser’s push subscription', function () {
    fakePush();

    $this->actingAs($this->user)->postJson('/account/push', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
        'keys' => ['p256dh' => 'BPublicKey', 'auth' => 'secret'],
        'contentEncoding' => 'aes128gcm',
    ])->assertOk();

    expect($this->user->pushSubscriptions()->sole()->public_key)->toBe('BPublicKey');

    $this->actingAs($this->user)->deleteJson('/account/push', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc'])->assertOk();
    expect($this->user->pushSubscriptions()->count())->toBe(0);

    $this->actingAs($this->user)->postJson('/account/push', ['endpoint' => 'http://insecure.example', 'keys' => ['p256dh' => 'x', 'auth' => 'y']])->assertUnprocessable();
});

it('shows the Telegram and push settings on the notifications page', function () {
    fakeTelegram();
    config(['webpush.vapid.public_key' => 'BTestKey']);
    fakePush();

    $this->actingAs($this->user)->get('/account/notifications')
        ->assertOk()
        ->assertSee(__('alerts.telegram.link'))
        ->assertSee(__('alerts.push.enable'));

    Livewire::actingAs($this->user)->test(NotificationChannels::class)
        ->call('linkTelegram')
        ->assertSet('telegramLink', fn (?string $link) => str_starts_with((string) $link, 'https://t.me/infokobuleti_test_bot?start='));
});
