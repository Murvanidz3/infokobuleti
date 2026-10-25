<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;

function currentTotp(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

it('asks for the authenticator code after a correct password', function () {
    staffMember();

    $this->post('/staff/login', ['email' => 'STAFF@example.com', 'password' => 'secret-password'])
        ->assertRedirect('/staff/two-factor');

    $this->assertGuest();
});

it('rejects wrong passwords, non-staff and blocked staff with a generic message', function () {
    [$staff] = staffMember();
    User::factory()->staff('secret-password')->create(['email' => 'user@example.com'])->assignRole(Role::User->value);

    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'wrong'])->assertSessionHasErrors(['email' => __('auth.failed')]);
    $this->post('/staff/login', ['email' => 'user@example.com', 'password' => 'secret-password'])->assertSessionHasErrors(['email' => __('auth.failed')]);

    $staff->forceFill(['is_blocked' => true])->save();
    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password'])->assertSessionHasErrors(['email' => __('auth.blocked')]);

    $this->assertGuest();
});

it('throttles password guessing', function () {
    staffMember();

    foreach (range(1, 5) as $attempt) {
        $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'wrong']);
    }

    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('signs staff in with a valid authenticator code', function () {
    [$staff, $secret] = staffMember();

    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password']);
    $this->get('/staff/two-factor')->assertOk()->assertSee(__('auth.staff.challenge_title'));

    $this->post('/staff/two-factor', ['code' => '000000'])->assertSessionHasErrors(['code' => __('auth.staff.invalid_code')]);
    $this->assertGuest();

    $this->post('/staff/two-factor', ['code' => currentTotp($secret)])->assertRedirect('/account/security');
    $this->assertAuthenticatedAs($staff);
});

it('does not accept the same authenticator code twice', function () {
    [$staff, $secret] = staffMember();
    $code = currentTotp($secret);

    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password']);
    $this->post('/staff/two-factor', ['code' => $code]);
    $this->post('/logout');

    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password']);
    $this->post('/staff/two-factor', ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('accepts each recovery code once', function () {
    [$staff] = staffMember();
    $code = $staff->two_factor_recovery_codes[0];

    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password']);
    $this->post('/staff/two-factor', ['recovery_code' => strtolower($code)])->assertRedirect('/account/security');
    $this->assertAuthenticatedAs($staff);
    expect($staff->fresh()->two_factor_recovery_codes)->toHaveCount(7)->not->toContain($code);

    $this->post('/logout');
    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password']);
    $this->post('/staff/two-factor', ['recovery_code' => $code])->assertSessionHasErrors('recovery_code');
    $this->assertGuest();
});

it('forces first-time staff to set up two-factor auth', function () {
    [$staff] = staffMember(withTwoFactor: false);

    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password'])
        ->assertRedirect('/staff/two-factor/setup');

    $this->get('/staff/two-factor')->assertRedirect('/staff/login');
    $this->get('/staff/two-factor/setup')->assertOk()->assertSee('<svg', false);

    $secret = session('auth.staff_pending_secret');

    $this->post('/staff/two-factor/setup', ['code' => '000000'])->assertSessionHasErrors('code');
    $this->assertGuest();

    $this->post('/staff/two-factor/setup', ['code' => currentTotp($secret)])
        ->assertRedirect('/staff/recovery-codes')
        ->assertSessionHas('recovery_codes');
    $codes = session('recovery_codes');

    $this->assertAuthenticatedAs($staff);
    expect($staff->fresh()->hasTwoFactorEnabled())->toBeTrue()
        ->and($staff->fresh()->two_factor_secret)->toBe($secret);

    $this->get('/staff/recovery-codes')->assertOk()->assertSee($codes[0]);
    $this->get('/staff/recovery-codes')->assertRedirect('/account/security');
});

it('expires a half-finished staff sign-in after ten minutes', function () {
    [, $secret] = staffMember();

    $this->post('/staff/login', ['email' => 'staff@example.com', 'password' => 'secret-password']);
    $this->travel(11)->minutes();

    $this->post('/staff/two-factor', ['code' => currentTotp($secret)])->assertRedirect('/staff/login');
    $this->assertGuest();
});

it('keeps the two-factor secret encrypted at rest', function () {
    [$staff, $secret] = staffMember();

    $raw = DB::table('users')->where('id', $staff->id)->value('two_factor_secret');

    expect($raw)->not->toBe($secret)->not->toContain($secret);
});

it('lets only staff with two-factor auth through the staff middleware', function () {
    Route::middleware(['web', 'staff'])->get('/_test/staff-only', fn () => 'ok');

    [$staff] = staffMember();
    [$withoutTwoFactor] = [User::factory()->staff()->create()->assignRole(Role::Moderator->value)];

    $this->get('/_test/staff-only')->assertRedirect('/staff/login');
    $this->actingAs(User::factory()->create())->get('/_test/staff-only')->assertForbidden();
    $this->actingAs($withoutTwoFactor)->get('/_test/staff-only')->assertForbidden();
    $this->actingAs($staff)->get('/_test/staff-only')->assertOk();
});

it('renders the staff pages in every language', function (string $prefix) {
    $this->get($prefix.'/staff/login')->assertOk()->assertSee('name="password"', false)->assertDontSee('data-login-dialog', false);
})->with(['', '/en', '/ru']);
