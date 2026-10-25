<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use App\Services\Sms\ArraySmsGateway;
use App\Services\Sms\SmsManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * The in-memory SMS gateway used by the tests (SMS_DRIVER=array in phpunit.xml).
 */
function smsGateway(): ArraySmsGateway
{
    /** @var ArraySmsGateway */
    return app(SmsManager::class)->driver('array');
}

/**
 * The 6-digit code from the last SMS that was sent.
 */
function lastSmsCode(): string
{
    preg_match('/\b(\d{6})\b/', smsGateway()->last()['text'] ?? '', $matches);

    return $matches[1] ?? '';
}

/**
 * A staff member with the given role and (optionally) two-factor auth already turned on.
 *
 * @return array{0: User, 1: string|null}
 */
function staffMember(Role $role = Role::Admin, bool $withTwoFactor = true): array
{
    test()->seed(RoleSeeder::class);

    $user = User::factory()->staff('secret-password')->create(['email' => 'staff@example.com']);
    $user->assignRole($role->value);

    $secret = null;

    if ($withTwoFactor) {
        $secret = app(TwoFactorAuthenticator::class)->generateSecret();
        app(TwoFactorAuthenticator::class)->enable($user, $secret);
    }

    return [$user->fresh(), $secret];
}
