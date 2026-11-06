<?php

use App\Enums\ListingStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationTopic;
use App\Livewire\Account\BusinessProfileForm;
use App\Livewire\Account\Dashboard;
use App\Livewire\Account\Favorites;
use App\Livewire\Account\MyListings;
use App\Livewire\Account\Notifications;
use App\Livewire\Account\Profile;
use App\Livewire\Account\SavedSearches;
use App\Livewire\Listings\PropertyIndex;
use App\Models\Classified;
use App\Models\DailyStat;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\Property;
use App\Models\SavedSearch;
use App\Models\User;
use App\Models\Vacancy;
use App\Notifications\ListingExpiry;
use App\Notifications\ListingModerated;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
});

it('sends guests to sign in and renders every account page for a member', function () {
    $this->get('/account')->assertRedirect('/login');

    foreach (['/account', '/account/listings', '/account/favorites', '/account/searches', '/account/notifications', '/account/profile', '/account/billing', '/account/security', '/en/account'] as $url) {
        $this->actingAs($this->user)->get($url)->assertOk()->assertSee('noindex', false);
    }
});

it('counts listings per group and shows only the owner’s own', function () {
    Property::factory()->published()->for($this->user)->create();
    Property::factory()->published()->for($this->user)->create(['expires_at' => now()->subDay()]);
    Hotel::factory()->pending()->for($this->user)->create();
    Classified::factory()->for($this->user)->create(['status' => ListingStatus::Rejected, 'rejection_reason' => 'Blurry photos']);
    Property::factory()->published()->create(['title' => ['ka' => 'Somebody else', 'en' => 'Somebody else']]);

    Livewire::actingAs($this->user)->test(Dashboard::class)
        ->assertViewHas('counts', fn (array $counts) => $counts['active'] === 1 && $counts['expired'] === 1 && $counts['pending'] === 1 && $counts['attention'] === 1 && $counts['total'] === 4)
        ->assertSee('Blurry photos')
        ->assertDontSee('Somebody else');

    Livewire::actingAs($this->user)->test(MyListings::class, ['type' => 'property'])
        ->assertViewHas('rows', fn (array $rows) => count($rows) === 2)
        ->set('group', 'expired')
        ->assertViewHas('rows', fn (array $rows) => count($rows) === 1 && $rows[0]['can']['renew'] === true);
});

it('shows phone reveals and favorites per listing', function () {
    $property = Property::factory()->published()->for($this->user)->create(['views_count' => 12]);
    DailyStat::factory()->create(['statable_type' => 'property', 'statable_id' => $property->id, 'date' => today(), 'phone_reveals' => 3, 'favorites' => 2]);
    DailyStat::factory()->create(['statable_type' => 'property', 'statable_id' => $property->id, 'date' => today()->subDay(), 'phone_reveals' => 1, 'favorites' => 0]);

    Livewire::actingAs($this->user)->test(MyListings::class, ['type' => 'property'])
        ->assertViewHas('rows', fn (array $rows) => $rows[0]['views'] === 12 && $rows[0]['phone_reveals'] === 4 && $rows[0]['favorites'] === 2);
});

it('pauses, resumes, renews and deletes the owner’s listing', function () {
    $listing = Property::factory()->published()->for($this->user)->create();
    $component = Livewire::actingAs($this->user)->test(MyListings::class, ['type' => 'property']);

    $component->call('pause', 'property', $listing->id);
    expect($listing->fresh()->status)->toBe(ListingStatus::Paused);

    $component->call('resume', 'property', $listing->id);
    expect($listing->fresh()->status)->toBe(ListingStatus::Published)
        ->and($listing->fresh()->moderated_by)->toBeNull();

    $listing->forceFill(['status' => ListingStatus::Expired, 'expires_at' => now()->subDays(2)])->save();
    $component->call('renew', 'property', $listing->id);
    expect($listing->fresh())
        ->status->toBe(ListingStatus::Published)
        ->expires_at->isAfter(now()->addDays(59))->toBeTrue();

    $component->call('delete', 'property', $listing->id);
    expect(Property::query()->find($listing->id))->toBeNull()
        ->and(Property::withTrashed()->find($listing->id))->not->toBeNull();
});

it('renews a listing about to expire without touching its status', function () {
    $listing = Vacancy::factory()->published()->for($this->user)->create(['expires_at' => now()->addDays(2)]);

    Livewire::actingAs($this->user)->test(MyListings::class, ['type' => 'vacancy'])->call('renew', 'vacancy', $listing->id);

    expect($listing->fresh()->expires_at->isAfter(now()->addDays(29)))->toBeTrue()
        ->and($listing->fresh()->status)->toBe(ListingStatus::Published);
});

it('refuses actions that do not fit the status and never touches other people’s listings', function () {
    $pending = Property::factory()->pending()->for($this->user)->create();
    $fresh = Property::factory()->published()->for($this->user)->create(['expires_at' => now()->addDays(40)]);
    $foreign = Property::factory()->published()->create();

    Livewire::actingAs($this->user)->test(MyListings::class, ['type' => 'property'])
        ->call('pause', 'property', $pending->id)
        ->assertDispatched('toast', type: 'error')
        ->call('renew', 'property', $fresh->id)
        ->assertDispatched('toast', type: 'error');

    expect($pending->fresh()->status)->toBe(ListingStatus::Pending)
        ->and($fresh->fresh()->expires_at->isBefore(now()->addDays(41)))->toBeTrue();

    Livewire::actingAs($this->user)->test(MyListings::class, ['type' => 'property'])->call('delete', 'property', $foreign->id)->assertNotFound();
    expect($foreign->fresh())->not->toBeNull();
});

it('lists favorites across modules and removes them', function () {
    $hotel = Hotel::factory()->published()->create(['title' => ['ka' => 'Sea Breeze', 'en' => 'Sea Breeze']]);
    $gone = Property::factory()->draft()->create(['title' => ['ka' => 'Hidden flat', 'en' => 'Hidden flat']]);
    $favorite = Favorite::factory()->create(['user_id' => $this->user->id, 'favoritable_type' => 'hotel', 'favoritable_id' => $hotel->id]);
    Favorite::factory()->create(['user_id' => $this->user->id, 'favoritable_type' => 'property', 'favoritable_id' => $gone->id]);

    Livewire::actingAs($this->user)->test(Favorites::class)
        ->assertSee('Sea Breeze')
        ->assertViewHas('items', fn (array $items) => collect($items)->firstWhere('title', 'Hidden flat')['available'] === false)
        ->set('type', 'hotel')
        ->assertViewHas('items', fn (array $items) => count($items) === 1)
        ->call('remove', $favorite->id);

    expect(Favorite::query()->where('user_id', $this->user->id)->count())->toBe(1);
});

it('edits, pauses and deletes saved searches', function () {
    $search = SavedSearch::factory()->create(['user_id' => $this->user->id, 'module' => 'properties', 'filters' => ['deal' => 'sale', 'rooms' => ['2']], 'name' => 'Flats']);
    $other = SavedSearch::factory()->create();

    Livewire::actingAs($this->user)->test(SavedSearches::class)
        ->assertSee('Flats')
        ->assertSee('/real-estate?deal=sale', false)
        ->assertSee('saved='.$search->id, false)
        ->call('edit', $search->id)
        ->set('name', 'Sale flats')
        ->set('channels', [NotificationChannel::Email->value])
        ->set('frequency', 'weekly')
        ->call('save')
        ->assertHasNoErrors()
        ->call('toggle', $search->id);

    expect($search->fresh())
        ->name->toBe('Sale flats')
        ->frequency->value->toBe('weekly')
        ->is_active->toBeFalse();

    Livewire::actingAs($this->user)->test(SavedSearches::class)->call('delete', $other->id)->assertNotFound();
    expect($other->fresh())->not->toBeNull();
});

it('updates a saved search from the list page when opened for editing', function () {
    $search = SavedSearch::factory()->create(['user_id' => $this->user->id, 'module' => 'properties', 'filters' => ['deal' => 'sale']]);

    Livewire::withQueryParams(['deal' => 'rent', 'saved' => $search->id])
        ->actingAs($this->user)
        ->test(PropertyIndex::class)
        ->call('saveSearch');

    expect($search->fresh()->filters)->toMatchArray(['deal' => 'rent'])
        ->and(SavedSearch::query()->where('user_id', $this->user->id)->count())->toBe(1);
});

it('shows notifications, opens them and stores email preferences', function () {
    $listing = Property::factory()->for($this->user)->create(['status' => ListingStatus::Rejected]);
    $this->user->notify(new ListingModerated($listing, approved: false, reason: 'Wrong category'));

    $component = Livewire::actingAs($this->user)->test(Notifications::class)
        ->assertSee('Wrong category');

    $id = $this->user->notifications()->first()->id;
    $component->call('open', $id)->assertRedirect(localized_route('account.listings', ['group' => 'attention']));
    expect($this->user->unreadNotifications()->count())->toBe(0);

    $component->set('preferences.moderation', false)->call('savePreferences');
    expect($this->user->fresh()->wantsEmailFor(NotificationTopic::Moderation))->toBeFalse()
        ->and($this->user->fresh()->wantsEmailFor(NotificationTopic::Messages))->toBeTrue();
});

it('respects email preferences when notifying', function () {
    $this->user->forceFill(['notification_preferences' => ['moderation' => false]])->save();
    $listing = Property::factory()->for($this->user)->create();

    expect((new ListingModerated($listing, true))->via($this->user))->toBe(['database']);

    $this->user->forceFill(['notification_preferences' => null])->save();
    expect((new ListingModerated($listing, true))->via($this->user->fresh()))->toBe(['database', 'mail']);
});

it('updates the profile and creates a business profile', function () {
    $individual = User::factory()->create(['account_type' => 'individual']);

    Livewire::actingAs($individual)->test(Profile::class)
        ->set('name', 'Nino Beridze')
        ->set('email', 'NINO@example.com')
        ->set('locale', 'en')
        ->call('save')
        ->assertHasNoErrors();

    expect($individual->fresh())->name->toBe('Nino Beridze')->email->toBe('nino@example.com')->locale->toBe('en');

    Livewire::actingAs($individual)->test(BusinessProfileForm::class)
        ->set('open', true)
        ->set('type', 'agency')
        ->set('name', 'Kobuleti Homes')
        ->set('phone', '599 12 34 56')
        ->set('description.ka', 'უძრავი ქონების სააგენტო')
        ->call('save')
        ->assertHasNoErrors();

    $individual->refresh();
    expect($individual->account_type->value)->toBe('business')
        ->and($individual->hasRole('business'))->toBeTrue()
        ->and($individual->businessProfiles()->first())
        ->name->toBe('Kobuleti Homes')
        ->phone->toBe('+995599123456')
        ->verified_at->toBeNull();
});

it('expires listings past their date and reminds owners three days before', function () {
    Notification::fake();
    $expired = Classified::factory()->published()->for($this->user)->create(['expires_at' => now()->subHour()]);
    $soon = Property::factory()->published()->for($this->user)->create(['expires_at' => today()->addDays(3)->setTime(15, 0)]);
    $later = Property::factory()->published()->for($this->user)->create(['expires_at' => today()->addDays(10)]);

    $this->artisan('listings:expire')->assertSuccessful();

    expect($expired->fresh()->status)->toBe(ListingStatus::Expired)
        ->and($later->fresh()->status)->toBe(ListingStatus::Published);

    Notification::assertSentTo($this->user, ListingExpiry::class, fn (ListingExpiry $notification) => $notification->expired && $notification->listing->is($expired));
    Notification::assertSentTo($this->user, ListingExpiry::class, fn (ListingExpiry $notification) => ! $notification->expired && $notification->listing->is($soon));
    Notification::assertSentTimes(ListingExpiry::class, 2);
});

it('puts unread badges and account links in the header menu', function () {
    $listing = Property::factory()->for($this->user)->create();
    $this->user->notify(new ListingModerated($listing, approved: true));

    $this->actingAs($this->user)->get('/')
        ->assertOk()
        ->assertSee(localized_route('account.listings'), false)
        ->assertSee(localized_route('listings.create'), false);
});
