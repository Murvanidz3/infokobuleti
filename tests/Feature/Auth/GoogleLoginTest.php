<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function fakeGoogle(array $attributes = []): void
{
    Socialite::fake('google', SocialiteUser::fake([
        'id' => '109876543210',
        'name' => 'Nino Beridze',
        'email' => 'nino@example.com',
        'email_verified' => true,
        'avatar' => 'https://lh3.googleusercontent.com/a/photo',
        ...$attributes,
    ]));
}

it('redirects to Google and remembers the language and return page', function () {
    fakeGoogle();

    $this->get('/en/auth/google?return_to='.urlencode(url('/en')))->assertRedirect();

    expect(session('auth.google'))->toMatchArray(['intent' => 'login', 'locale' => 'en', 'remember' => true, 'return_to' => url('/en')]);
});

it('explains when Google sign-in is not configured', function () {
    config(['services.google.client_id' => null]);

    $this->get('/auth/google')->assertRedirect('/login')->assertSessionHas('error', __('auth.google.not_configured'));
});

it('registers a new user in one step', function () {
    fakeGoogle();

    $this->withSession(['auth.google' => ['intent' => 'login', 'locale' => 'ka', 'remember' => true, 'return_to' => null]])
        ->get('/auth/google/callback?code=abc&state=xyz')
        ->assertRedirect('/onboarding');

    $user = User::sole();

    expect($user)
        ->google_id->toBe('109876543210')
        ->email->toBe('nino@example.com')
        ->name->toBe('Nino Beridze')
        ->avatar->toBe('https://lh3.googleusercontent.com/a/photo')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole(Role::User->value))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('links Google to the account with the same verified email', function () {
    $user = User::factory()->phoneOnly()->create(['email' => 'nino@example.com', 'onboarded_at' => now()]);
    fakeGoogle();

    $this->get('/auth/google/callback?code=abc')->assertRedirect('/');

    expect($user->fresh()->google_id)->toBe('109876543210')
        ->and(User::count())->toBe(1);
    $this->assertAuthenticatedAs($user);
});

it('does not link by an unverified email', function () {
    User::factory()->create(['email' => 'nino@example.com']);
    fakeGoogle(['email_verified' => false]);

    $this->get('/auth/google/callback?code=abc')->assertRedirect('/login')->assertSessionHas('error', __('auth.google.failed'));

    $this->assertGuest();
});

it('signs in a returning Google user and keeps the language', function () {
    $user = User::factory()->google()->create(['google_id' => '109876543210']);
    fakeGoogle(['email' => 'changed@example.com']);

    $this->withSession(['auth.google' => ['intent' => 'login', 'locale' => 'ru', 'remember' => false, 'return_to' => url('/ru')]])
        ->get('/auth/google/callback?code=abc')
        ->assertRedirect('/ru');

    $this->assertAuthenticatedAs($user);
});

it('handles a cancelled consent screen', function () {
    $this->get('/auth/google/callback?error=access_denied')
        ->assertRedirect('/login')
        ->assertSessionHas('error', __('auth.google.cancelled'));

    $this->assertGuest();
});

it('handles Google errors gracefully', function () {
    Socialite::fake('google', fn () => throw new InvalidStateException);

    $this->get('/auth/google/callback?code=abc')->assertRedirect('/login')->assertSessionHas('error', __('auth.google.failed'));
});

it('refuses blocked users and staff', function () {
    User::factory()->blocked()->create(['email' => 'nino@example.com']);
    fakeGoogle();

    $this->get('/auth/google/callback?code=abc')->assertSessionHas('error', __('auth.blocked'));
    $this->assertGuest();

    [$staff] = staffMember();
    fakeGoogle(['id' => '555', 'email' => $staff->email]);

    $this->get('/auth/google/callback?code=abc')->assertSessionHas('error', __('auth.staff_use_staff_login'));
    $this->assertGuest();
});

it('links Google from the account settings', function () {
    $user = User::factory()->phoneOnly()->create(['onboarded_at' => now()]);
    fakeGoogle();

    $this->actingAs($user)->get('/auth/google?intent=link')->assertRedirect();
    expect(session('auth.google.intent'))->toBe('link');

    $this->actingAs($user)
        ->withSession(['auth.google' => ['intent' => 'link', 'locale' => 'ka', 'remember' => true, 'return_to' => null]])
        ->get('/auth/google/callback?code=abc')
        ->assertRedirect('/account/security')
        ->assertSessionHas('status', __('auth.google.linked'));

    expect($user->fresh())->google_id->toBe('109876543210')->email->toBe('nino@example.com');
});

it('refuses to link a Google account that belongs to someone else', function () {
    User::factory()->google()->create(['google_id' => '109876543210']);
    $user = User::factory()->phoneOnly()->create(['onboarded_at' => now()]);
    fakeGoogle();

    $this->actingAs($user)
        ->withSession(['auth.google' => ['intent' => 'link', 'locale' => 'ka', 'remember' => true, 'return_to' => null]])
        ->get('/auth/google/callback?code=abc')
        ->assertRedirect('/account/security')
        ->assertSessionHas('error', __('auth.google.taken'));

    expect($user->fresh()->google_id)->toBeNull();
});
