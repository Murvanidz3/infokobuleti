<?php

use App\Enums\Role;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function staffWith(Role $role, bool $twoFactor = true): User
{
    $user = User::factory()->staff()->create();
    $user->assignRole($role->value);

    if ($twoFactor) {
        app(TwoFactorAuthenticator::class)->enable($user, app(TwoFactorAuthenticator::class)->generateSecret());
    }

    return $user->fresh();
}

it('sends guests to the staff sign-in', function () {
    $this->get('/admin')->assertRedirect('/staff/login');
    $this->get('/admin/hotels')->assertRedirect('/staff/login');
});

it('keeps regular users, staff without two-factor and blocked staff out', function () {
    $this->actingAs(User::factory()->create()->assignRole(Role::User->value))->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->create()->assignRole(Role::Business->value))->get('/admin')->assertForbidden();
    $this->actingAs(staffWith(Role::Admin, twoFactor: false))->get('/admin')->assertForbidden();

    $blocked = staffWith(Role::Admin);
    $blocked->forceFill(['is_blocked' => true])->save();
    $this->actingAs($blocked)->get('/admin')->assertForbidden();
    $this->get('/')->assertRedirect('/login');
    $this->assertGuest();
});

it('limits moderators to the moderation screens', function () {
    $this->actingAs(staffWith(Role::Moderator));

    foreach (['/admin', '/admin/moderation', '/admin/reviews', '/admin/reports'] as $url) {
        $this->get($url)->assertOk();
    }

    foreach (['/admin/users', '/admin/hotels', '/admin/properties', '/admin/categories', '/admin/payments', '/admin/price-plans', '/admin/settings', '/admin/activities'] as $url) {
        $this->get($url)->assertForbidden();
    }

    $this->get('/admin')->assertSee(__('admin.moderation.queue'))->assertDontSee(__('admin.resources.users.plural'));
});

it('lets admins manage everything except system settings', function () {
    $this->actingAs(staffWith(Role::Admin));

    foreach (['/admin/users', '/admin/hotels', '/admin/categories', '/admin/payments', '/admin/moderation', '/admin/homepage'] as $url) {
        $this->get($url)->assertOk();
    }

    $this->get('/admin/settings')->assertForbidden();
});

it('lets super admins open the settings', function () {
    $this->actingAs(staffWith(Role::SuperAdmin))->get('/admin/settings')->assertOk()->assertSee(__('admin.settings.title'));
});

it('lets only super admins hand out staff roles', function () {
    $this->actingAs(staffWith(Role::Admin));
    expect(UserResource::assignableRoles())->toHaveKeys(['business', 'user'])->not->toHaveKeys(['super_admin', 'admin', 'moderator']);

    $this->actingAs(staffWith(Role::SuperAdmin));
    expect(UserResource::assignableRoles())->toHaveKeys(['super_admin', 'admin', 'moderator', 'business', 'user']);
});

it('navigates inside the admin without a full reload and preloads the georgian fonts', function () {
    $html = $this->actingAs(staffWith(Role::Admin))->get('/admin')->assertOk()->getContent();

    expect($html)->toContain('class="fi dark"')
        ->and($html)->not->toContain('fi-theme-switcher')
        ->and($html)->toContain('data-nav-group="moderation"')
        ->and($html)->toContain('data-nav-group="listings"')
        ->and($html)->toContain('data-nav-group="content"')
        ->and($html)->toContain('data-nav-group="users"')
        ->and($html)->toContain('data-nav-group="catalog"')
        ->and($html)->toContain('data-nav-group="finance"')
        ->and($html)->toContain('wire:navigate.hover')
        ->and($html)->toContain('rel="preload"')
        ->and($html)->toContain('noto-sans-georgian-georgian')
        ->and($html)->toContain('noto-sans-georgian-latin')
        ->and($html)->toContain('noto-sans-cyrillic')
        ->and($html)->toContain('livewire:navigated');

    expect($html)->toMatch('/href="[^"]*\/admin\/hotels"[^>]*wire:navigate/');

    preg_match('/<a\b[^>]*href="[^"]*\/admin\/locale\/en"[^>]*>/', $html, $locale);
    expect($locale[0] ?? 'missing locale link')->not->toContain('wire:navigate');

    preg_match('/<form\b[^>]*action="[^"]*\/logout"[^>]*>/', $html, $logout);
    expect($logout[0] ?? 'missing logout form')->not->toContain('wire:navigate');

    preg_match('/<a\b[^>]*href="[^"]*\/account\/[^"]*"[^>]*>/', $html, $account);
    expect($account[0] ?? 'missing account link')->not->toContain('wire:navigate');
});

it('marks the sms and system sidebar groups for a super admin', function () {
    $html = $this->actingAs(staffWith(Role::SuperAdmin))->get('/admin')->assertOk()->getContent();

    expect($html)->toContain('data-nav-group="sms"')
        ->and($html)->toContain('data-nav-group="system"');
});

it('shows the admin in Georgian by default', function () {
    $this->actingAs(staffWith(Role::Admin))->get('/admin')
        ->assertSee(__('admin.dashboard.title', [], 'ka'))
        ->assertSee(__('admin.nav.listings', [], 'ka'));
});

it('switches the admin to English', function () {
    $this->actingAs(staffWith(Role::Admin));

    $this->get('/admin/locale/en')->assertRedirect();
    $this->get('/admin')->assertSee(__('admin.dashboard.title', [], 'en'))->assertSee('Moderation queue')->assertSee('Listings');
    $this->get('/admin/locale/de')->assertNotFound();
});
