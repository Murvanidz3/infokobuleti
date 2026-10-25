<?php

use App\Livewire\Account\PhoneForm;
use App\Livewire\Account\Security;
use App\Models\PhoneVerification;
use App\Models\User;
use App\Models\UserSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('requires sign-in for the account pages', function () {
    $this->get('/account/security')->assertRedirect('/login');
    $this->get('/en/account/security')->assertRedirect('/en/login');
});

it('shows sign-in methods and devices', function () {
    $user = User::factory()->google()->create(['phone' => '+995555123456', 'phone_verified_at' => now()]);

    $this->actingAs($user)->get('/account/security')
        ->assertOk()
        ->assertSee('+995 555 12 34 56')
        ->assertSee(__('auth.methods.connected'))
        ->assertSee(__('auth.sessions.this_device'));
});

it('unlinks Google while the phone remains', function () {
    $user = User::factory()->google()->create(['phone' => '+995555123456', 'phone_verified_at' => now()]);

    Livewire::actingAs($user)->test(Security::class)
        ->call('unlinkGoogle')
        ->assertDispatched('toast', message: __('auth.google.unlinked'), type: 'success');

    expect($user->fresh()->google_id)->toBeNull();
});

it('keeps the last sign-in method', function () {
    $user = User::factory()->google()->create();

    Livewire::actingAs($user)->test(Security::class)
        ->call('unlinkGoogle')
        ->assertDispatched('toast', message: __('auth.methods.last_method'), type: 'error');

    expect($user->fresh()->google_id)->not->toBeNull();

    $phoneOnly = User::factory()->phoneOnly()->create();

    Livewire::actingAs($phoneOnly)->test(Security::class)->call('removePhone');

    expect($phoneOnly->fresh()->hasVerifiedPhone())->toBeTrue();
});

it('removes the phone when Google remains', function () {
    $user = User::factory()->create(['google_id' => '123', 'phone' => '+995555123456', 'phone_verified_at' => now()]);

    Livewire::actingAs($user)->test(Security::class)->call('removePhone');

    expect($user->fresh())->phone->toBeNull()->phone_verified_at->toBeNull();
});

it('changes the phone only after a code sent to the new number', function () {
    $user = User::factory()->create(['phone' => '+995555111111']);

    $component = Livewire::actingAs($user)->test(PhoneForm::class)
        ->set('phone', '599 22 33 44')
        ->call('sendCode')
        ->assertHasNoErrors()
        ->assertSet('step', 'code')
        ->assertSet('resendIn', 60);

    expect(smsGateway()->last()['to'])->toBe('+995599223344')
        ->and($user->fresh()->phone)->toBe('+995555111111')
        ->and(PhoneVerification::sole()->user_id)->toBe($user->id);

    $component->set('code', lastSmsCode() === '000000' ? '111111' : '000000')->call('confirm')->assertHasErrors('code');
    expect($user->fresh()->phone)->toBe('+995555111111');

    $component->set('code', lastSmsCode())->call('confirm')->assertHasNoErrors()->assertDispatched('phone-updated');
    expect($user->fresh())->phone->toBe('+995599223344')->phone_verified_at->not->toBeNull();
});

it('refuses a number that belongs to another account', function () {
    User::factory()->create(['phone' => '+995599223344']);
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(PhoneForm::class)
        ->set('phone', '599223344')
        ->call('sendCode')
        ->assertHasErrors(['phone' => __('auth.phone.taken')]);

    expect(smsGateway()->messages)->toBeEmpty();
});

it('lets Google users add a phone and continue to where they were going', function () {
    $user = User::factory()->google()->create();
    session(['url.intended' => url('/en')]);

    $component = Livewire::actingAs($user)->test(PhoneForm::class, ['context' => 'verify'])
        ->set('phone', '599223344')
        ->call('sendCode');

    $component->set('code', lastSmsCode())->call('confirm')->assertRedirect(url('/en'));

    expect($user->fresh()->hasVerifiedPhone())->toBeTrue();
});

it('records the device on sign-in and forgets it on sign-out', function () {
    $user = User::factory()->create(['phone' => '+995555123456']);

    $this->postJson('/auth/phone/send', ['phone' => '555123456']);
    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0 Safari/537.36')
        ->postJson('/auth/phone/verify', ['phone' => '555123456', 'code' => lastSmsCode(), 'remember' => true])
        ->assertOk();

    $row = UserSession::sole();

    expect($row->user_id)->toBe($user->id)
        ->and($row->remembered)->toBeTrue()
        ->and($row->agent()->label())->toBe('Chrome · Windows');

    $this->post('/logout')->assertRedirect('/');

    expect(UserSession::count())->toBe(0);
    $this->assertGuest();
});

it('signs out other devices and invalidates their remember cookies', function () {
    $user = User::factory()->create(['remember_token' => 'old-token']);
    $other = $user->userSessions()->create([
        'device_id' => (string) Str::ulid(),
        'session_id' => 'other-session-id',
        'last_active_at' => now()->subHour(),
    ]);

    $this->actingAs($user)->get('/account/security')->assertOk();

    Livewire::actingAs($user)->test(Security::class)
        ->call('logoutOtherDevices')
        ->assertDispatched('toast');

    expect(UserSession::find($other->id))->toBeNull()
        ->and($user->fresh()->getRememberToken())->not->toBe('old-token');
});

it('signs a browser out when its device was removed elsewhere', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/account/security')->assertOk();
    expect(UserSession::count())->toBe(1);

    UserSession::query()->delete();

    $this->get('/account/security')->assertRedirect('/login')->assertSessionHas('status', __('auth.session_ended'));
    $this->assertGuest();
});

it('signs blocked users out on their next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/account/security')->assertOk();

    $user->forceFill(['is_blocked' => true])->save();

    $this->get('/account/security')->assertRedirect('/login')->assertSessionHas('error', __('auth.blocked'));
    $this->assertGuest();

    $this->actingAs($user->fresh())->getJson('/account/security')->assertForbidden();
});

it('requires a verified phone where the phone.verified middleware applies', function () {
    Route::middleware(['web', 'auth', 'phone.verified'])->get('/_test/post', fn () => 'ok');

    $this->actingAs(User::factory()->google()->create())
        ->get('/_test/post')
        ->assertRedirect('/account/phone');

    expect(session('url.intended'))->toBe(url('/_test/post'));

    $this->actingAs(User::factory()->google()->create())->getJson('/_test/post')->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/_test/post')->assertOk();
});

it('shows staff their two-factor status and regenerates recovery codes', function () {
    [$staff] = staffMember();
    $old = $staff->two_factor_recovery_codes;

    $this->actingAs($staff)->get('/account/security')->assertOk()->assertSee(__('auth.methods.two_factor'));

    $component = Livewire::actingAs($staff)->test(Security::class)->call('regenerateRecoveryCodes');

    expect($component->get('recoveryCodes'))->toHaveCount(8)
        ->and($staff->fresh()->two_factor_recovery_codes)->toBe($component->get('recoveryCodes'))->not->toBe($old);
});
