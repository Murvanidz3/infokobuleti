<?php

use App\Enums\AccountType;
use App\Enums\BusinessType;
use App\Enums\Role;
use App\Livewire\Onboarding;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('sends new accounts to onboarding before the account pages', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)->get('/account/security')->assertRedirect('/onboarding');
    $this->actingAs($user)->get('/onboarding')->assertOk()->assertSeeLivewire(Onboarding::class);
});

it('saves an individual account and returns to the intended page', function () {
    $user = User::factory()->notOnboarded()->create();

    Livewire::actingAs($user)
        ->withQueryParams([])
        ->test(Onboarding::class)
        ->assertDontSee(__('auth.onboarding.email_hint'))
        ->set('firstName', '  Giorgi ')
        ->set('lastName', 'Tsintsadze ')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(url('/'));

    expect($user->fresh())
        ->name->toBe('Giorgi Tsintsadze')
        ->account_type->toBe(AccountType::Individual)
        ->onboarded_at->not->toBeNull();
});

it('creates the business profile for business accounts', function () {
    $user = User::factory()->notOnboarded()->create();

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->set('firstName', 'Tamar')
        ->set('lastName', 'Khalvashi')
        ->set('accountType', 'business')
        ->call('save')
        ->assertHasErrors(['businessType', 'businessName'])
        ->set('businessType', BusinessType::Hotel->value)
        ->set('businessName', 'Hotel Sanapiro')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->account_type)->toBe(AccountType::Business)
        ->and($user->hasRole(Role::Business->value))->toBeTrue()
        ->and($user->businessProfiles()->sole())->type->toBe(BusinessType::Hotel)->name->toBe('Hotel Sanapiro')->phone->toBe($user->phone);
});

it('asks Google accounts for a phone after onboarding', function () {
    $user = User::factory()->google()->notOnboarded()->create(['name' => 'Nino Beridze']);

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->assertSet('firstName', 'Nino')
        ->assertSet('lastName', 'Beridze')
        ->call('save')
        ->assertRedirect(url('/account/phone'));
});

it('validates the name in the page language', function () {
    app()->setLocale('ka');

    Livewire::actingAs(User::factory()->notOnboarded()->create())
        ->test(Onboarding::class)
        ->set('firstName', '')
        ->set('lastName', '')
        ->call('save')
        ->assertHasErrors(['firstName' => 'required', 'lastName' => 'required']);

    expect(__('validation.required', ['attribute' => __('auth.onboarding.first_name')]))->toContain('სავალდებულოა');
});

it('asks phone sign-ups for their email and keeps it unverified', function () {
    $user = User::factory()->phoneOnly()->notOnboarded()->create(['name' => null]);
    User::factory()->create(['email' => 'taken@example.com']);

    $component = Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->assertSee(__('auth.onboarding.email_hint'))
        ->set('firstName', 'Nino')
        ->set('lastName', 'Beridze')
        ->call('save')
        ->assertHasErrors(['email' => 'required'])
        ->set('email', 'Taken@Example.com')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);

    expect($component->errors()->first('email'))->toBe(__('auth.onboarding.email_taken'));

    $component->set('email', ' Nino@Example.com ')->call('save')->assertHasNoErrors();

    expect($user->fresh())
        ->name->toBe('Nino Beridze')
        ->email->toBe('nino@example.com')
        ->email_verified_at->toBeNull()
        ->onboarded_at->not->toBeNull();
});

it('signs up a new number end to end: code, then onboarding', function () {
    $this->postJson('/auth/phone/send', ['phone' => '577 11 22 33'])->assertOk();

    $this->postJson('/auth/phone/verify', ['phone' => '577 11 22 33', 'code' => lastSmsCode()])
        ->assertOk()
        ->assertJson(['redirect' => url('/onboarding')]);

    $user = User::query()->where('phone', '+995577112233')->sole();
    expect($user)->onboarded_at->toBeNull()->phone_verified_at->not->toBeNull();

    $this->get('/account/security')->assertRedirect('/onboarding');
});
