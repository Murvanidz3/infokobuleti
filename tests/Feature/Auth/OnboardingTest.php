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
        ->set('name', '  Giorgi Tsintsadze ')
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
        ->set('name', 'Tamar Khalvashi')
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
    $user = User::factory()->google()->notOnboarded()->create();

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->set('name', 'Nino')
        ->call('save')
        ->assertRedirect(url('/account/phone'));
});

it('validates the name in the page language', function () {
    app()->setLocale('ka');

    Livewire::actingAs(User::factory()->notOnboarded()->create())
        ->test(Onboarding::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    expect(__('validation.required', ['attribute' => __('auth.onboarding.name')]))->toContain('სავალდებულოა');
});
