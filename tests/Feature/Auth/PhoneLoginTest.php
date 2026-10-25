<?php

use App\Enums\Role;
use App\Enums\SmsStatus;
use App\Models\PhoneVerification;
use App\Models\SmsMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function sendCode(string $phone = '555 12 34 56', string $prefix = ''): TestResponse
{
    return test()->postJson($prefix.'/auth/phone/send', ['phone' => $phone]);
}

function verifyCode(string $code, array $extra = [], string $phone = '555 12 34 56'): TestResponse
{
    return test()->postJson('/auth/phone/verify', ['phone' => $phone, 'code' => $code, ...$extra]);
}

it('sends a 6-digit code and logs the SMS without storing the code', function () {
    sendCode()
        ->assertOk()
        ->assertJson(['phone' => '+995 555 12 34 56', 'resend_in' => 60, 'expires_in' => 300])
        ->assertJsonMissingPath('dev_code');

    $code = lastSmsCode();
    $verification = PhoneVerification::sole();

    expect($code)->toMatch('/^\d{6}$/')
        ->and(smsGateway()->last()['to'])->toBe('+995555123456')
        ->and(smsGateway()->last()['text'])->toContain(__('auth.sms.code', ['code' => $code, 'minutes' => 5], 'ka'))
        ->and(smsGateway()->last()['text'])->toEndWith('#'.$code)
        ->and($verification->code_hash)->not->toContain($code)
        ->and($verification->expires_at->diffInSeconds(now(), true))->toBeGreaterThan(295)
        ->and(SmsMessage::sole()->status)->toBe(SmsStatus::Sent);
});

it('writes the SMS in the language of the page', function () {
    sendCode(prefix: '/ru')->assertOk();

    expect(smsGateway()->last()['text'])->toContain('ваш код '.lastSmsCode());
});

it('creates an account for a new number and sends it to onboarding', function () {
    sendCode();

    $response = verifyCode(lastSmsCode())->assertOk();

    $user = User::sole();

    expect($response->json('redirect'))->toBe(url('/onboarding'))
        ->and($user->phone)->toBe('+995555123456')
        ->and($user->hasVerifiedPhone())->toBeTrue()
        ->and($user->hasRole(Role::User->value))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('signs an existing user in and returns them to the page they came from', function () {
    $user = User::factory()->create(['phone' => '+995555123456', 'phone_verified_at' => null, 'onboarded_at' => now()]);

    sendCode();
    verifyCode(lastSmsCode(), ['return_to' => url('/en')])
        ->assertOk()
        ->assertJson(['redirect' => url('/en')]);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->hasVerifiedPhone())->toBeTrue();
});

it('ignores return addresses on other sites', function () {
    User::factory()->create(['phone' => '+995555123456', 'onboarded_at' => now()]);

    sendCode();
    verifyCode(lastSmsCode(), ['return_to' => 'https://evil.example/phish'])->assertJson(['redirect' => url('/')]);
});

it('rejects a wrong code and reports the attempts left', function () {
    sendCode();
    $wrong = lastSmsCode() === '000000' ? '111111' : '000000';

    verifyCode($wrong)
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0', trans_choice('enums.otp_error.invalid', 4, ['count' => 4]));

    $this->assertGuest();
});

it('expires codes after five minutes', function () {
    sendCode();
    $code = lastSmsCode();

    $this->travel(301)->seconds();

    verifyCode($code)->assertUnprocessable()->assertJsonPath('restart', true)->assertJsonPath('message', __('enums.otp_error.expired'));
    $this->assertGuest();
});

it('locks a code after five wrong attempts, even for the right code', function () {
    sendCode();
    $code = lastSmsCode();
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 4) as $attempt) {
        verifyCode($wrong)->assertUnprocessable();
    }

    verifyCode($wrong)->assertUnprocessable()->assertJsonPath('message', __('enums.otp_error.too_many_attempts'));
    verifyCode($code)->assertUnprocessable()->assertJsonPath('message', __('enums.otp_error.too_many_attempts'));

    $this->assertGuest();
});

it('allows a new code only after 60 seconds, and the new code replaces the old one', function () {
    sendCode();
    $first = lastSmsCode();

    sendCode()->assertStatus(429)->assertJsonPath('retry_after', 60);

    $this->travel(61)->seconds();
    sendCode()->assertOk();
    $second = lastSmsCode();

    if ($first !== $second) {
        verifyCode($first)->assertUnprocessable();
    }

    verifyCode($second)->assertOk();
});

it('limits codes per phone number per hour', function () {
    foreach (range(1, 5) as $i) {
        sendCode()->assertOk();
        $this->travel(61)->seconds();
    }

    sendCode()->assertStatus(429)->assertJsonPath('message', __('enums.otp_error.phone_limit', ['minutes' => 55]));
});

it('limits codes per IP address', function () {
    foreach (range(0, 19) as $i) {
        sendCode('555 00 00 '.str_pad((string) $i, 2, '0', STR_PAD_LEFT))->assertOk();
        $this->travel(7)->seconds();
    }

    sendCode('555 99 99 99')->assertStatus(429);
});

it('validates the phone number and the bot check', function () {
    sendCode('422 29 00 00')->assertUnprocessable()->assertJsonValidationErrors(['phone' => __('auth.phone.invalid')]);

    $this->postJson('/auth/phone/send', ['phone' => '555123456', 'captcha_token' => 'fail'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['captcha_token' => __('auth.captcha_failed')]);

    expect(smsGateway()->messages)->toBeEmpty();
});

it('does not sign in blocked users', function () {
    User::factory()->blocked()->create(['phone' => '+995555123456']);

    sendCode();
    verifyCode(lastSmsCode())->assertForbidden()->assertJson(['message' => __('auth.blocked')]);

    $this->assertGuest();
});

it('sends staff to the staff sign-in instead', function () {
    [$staff] = staffMember();
    $staff->update(['phone' => '+995555123456']);

    sendCode();
    verifyCode(lastSmsCode())->assertForbidden()->assertJson(['message' => __('auth.staff_use_staff_login')]);

    $this->assertGuest();
});

it('remembers the device for 30 days only when asked', function () {
    User::factory()->create(['phone' => '+995555123456', 'onboarded_at' => now()]);

    sendCode();
    $response = verifyCode(lastSmsCode(), ['remember' => true]);
    $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

    expect($cookie)->not->toBeNull()
        ->and((int) round(($cookie->getExpiresTime() - time()) / 86400))->toBe(30);
});

it('does not set a remember cookie without "remember me"', function () {
    User::factory()->create(['phone' => '+995555123456', 'onboarded_at' => now()]);

    sendCode();
    $response = verifyCode(lastSmsCode(), ['remember' => false]);

    expect(collect($response->headers->getCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_')))->toBeNull();
});

it('shows the code on screen only while testing with the log driver', function () {
    config(['sms.reveal_codes' => true]);
    sendCode()->assertJsonMissingPath('dev_code');

    config(['sms.default' => 'log']);
    $this->travel(61)->seconds();
    sendCode()->assertJsonPath('dev_code', fn ($code) => preg_match('/^\d{6}$/', $code) === 1);

    app()->detectEnvironment(fn () => 'production');
    $this->travel(61)->seconds();
    sendCode()->assertJsonMissingPath('dev_code');
});

it('marks the SMS as failed when the gateway fails', function () {
    smsGateway()->failing = true;

    sendCode()->assertOk();

    expect(SmsMessage::sole())->status->toBe(SmsStatus::Failed)->error->toContain('fail');
});

it('renders the sign-in page and the header dialog for guests', function () {
    $this->get('/login')->assertOk()->assertSee(__('auth.login.google'))->assertSee('autocomplete="one-time-code"', false);
    $this->get('/')->assertOk()->assertSee('data-login-dialog', false)->assertSee('data-login-panel', false);
    $this->get('/en/login')->assertOk()->assertSee(__('auth.login.title', [], 'en'));
});
