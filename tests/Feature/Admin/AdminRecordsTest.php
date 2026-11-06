<?php

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Filament\Pages\HomepageCuration;
use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Hotels\Pages\CreateHotel;
use App\Filament\Resources\Hotels\Pages\EditHotel;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Hotel;
use App\Models\Page;
use App\Models\PricePlan;
use App\Models\Property;
use App\Models\User;
use App\Services\Settings;
use Database\Seeders\ReferenceDataSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(ReferenceDataSeeder::class);
    [$this->admin] = staffMember(Role::SuperAdmin);
    $this->actingAs($this->admin);
    Filament::setServingStatus();
});

it('seeds the static pages and the price list', function () {
    expect(Page::query()->pluck('slug')->all())->toContain('terms', 'privacy', 'about')
        ->and(PricePlan::query()->where('code', 'vip-7')->value('price'))->toBe('10.00');
});

it('saves KA, EN and RU fields and drops empty languages', function () {
    $owner = User::factory()->create();

    Livewire::test(CreateHotel::class)
        ->fillForm([
            'title' => ['ka' => 'სასტუმრო ზღვა', 'en' => 'Hotel Sea', 'ru' => ''],
            'type' => 'hotel',
            'user_id' => $owner->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $hotel = Hotel::query()->latest('id')->firstOrFail();

    expect($hotel->getTranslations('title'))->toEqualCanonicalizing(['ka' => 'სასტუმრო ზღვა', 'en' => 'Hotel Sea'])
        ->and($hotel->status)->toBe(ListingStatus::Draft);

    Livewire::test(EditHotel::class, ['record' => $hotel->getRouteKey()])
        ->assertSchemaStateSet(['title.en' => 'Hotel Sea'])
        ->fillForm(['title' => ['ka' => 'სასტუმრო ზღვა', 'en' => '', 'ru' => 'Отель Море']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($hotel->fresh()->getTranslations('title'))->toEqualCanonicalizing(['ka' => 'სასტუმრო ზღვა', 'ru' => 'Отель Море']);
});

it('logs staff edits with the changed attributes', function () {
    $hotel = Hotel::factory()->create(['stars' => 3]);

    Livewire::test(EditHotel::class, ['record' => $hotel->getRouteKey()])
        ->fillForm(['stars' => 5])
        ->call('save')
        ->assertHasNoFormErrors();

    $activity = Activity::query()->where('log_name', 'admin')->where('subject_type', 'hotel')->where('subject_id', $hotel->id)->latest('id')->firstOrFail();

    expect($activity->causer_id)->toBe($this->admin->id)
        ->and($activity->attribute_changes['attributes']['stars'])->toBe(5)
        ->and($activity->attribute_changes['old']['stars'])->toBe(3);
});

it('does not log changes made outside the admin', function () {
    Filament::setServingStatus(false);

    Hotel::factory()->create()->update(['stars' => 4]);

    expect(Activity::query()->where('log_name', 'admin')->count())->toBe(0);
});

it('blocks and unblocks users', function () {
    $user = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('block')->table($user), data: ['reason' => 'spam'])
        ->assertHasNoActionErrors();

    expect($user->fresh())->is_blocked->toBeTrue()->blocked_reason->toBe('spam');

    Livewire::test(ListUsers::class)->callAction(TestAction::make('unblock')->table($user->fresh()));

    expect($user->fresh()->is_blocked)->toBeFalse();
});

it('lets staff view the site as a user and come back', function () {
    Filament::setServingStatus(false);
    $user = User::factory()->create(['name' => 'Nino']);

    $this->post(route('impersonation.start', $user))->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    $this->get('/')->assertSee(__('auth.impersonation.notice', ['name' => 'Nino']));

    $this->post(route('impersonation.leave'))->assertRedirect('/admin/users');
    $this->assertAuthenticatedAs($this->admin);
});

it('never impersonates staff or blocked users', function () {
    [$otherStaff] = [User::factory()->staff()->create()->assignRole(Role::Moderator->value)];
    $blocked = User::factory()->blocked()->create();

    $this->post(route('impersonation.start', $otherStaff))->assertForbidden();
    $this->post(route('impersonation.start', $blocked))->assertForbidden();
    $this->assertAuthenticatedAs($this->admin);
});

it('saves site settings and uses them on the site', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'contacts' => ['phone' => '+995 555 00 00 00', 'email' => 'hello@infokobuleti.com'],
            'listings' => ['expiry_days' => ['property' => 45, 'hotel' => 365, 'classified' => 30, 'vacancy' => 30]],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $settings = app(Settings::class);
    $settings->flush();

    expect($settings->get('contacts.phone'))->toBe('+995 555 00 00 00')
        ->and($settings->expiryDays('property'))->toBe(45)
        ->and((new Property)->expiryDays())->toBe(45);

    Filament::setServingStatus(false);
    $this->get('/')->assertSee('+995 555 00 00 00');
});

it('puts the site in maintenance mode for visitors only', function () {
    Filament::setServingStatus(false);
    app(Settings::class)->put(['site.maintenance' => true, 'site.maintenance_message.ka' => 'ვბრუნდებით 18:00-ზე']);

    auth()->logout();
    $this->get('/')->assertStatus(503)->assertSee('ვბრუნდებით 18:00-ზე');
    $this->get('/staff/login')->assertOk();

    $this->actingAs($this->admin)->get('/')->assertOk();
});

it('saves the homepage curation', function () {
    $hotel = Hotel::factory()->create(['status' => ListingStatus::Published, 'published_at' => now()]);

    Livewire::test(HomepageCuration::class)
        ->fillForm(['featured_hotels' => [$hotel->id], 'season' => 'summer'])
        ->call('save')
        ->assertHasNoErrors();

    app(Settings::class)->flush();

    expect(app(Settings::class)->get('homepage.featured_hotels'))->toBe([$hotel->id])
        ->and(app(Settings::class)->get('homepage.season'))->toBe('summer');
});
