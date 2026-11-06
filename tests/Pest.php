<?php

use App\Actions\Billing\StartCardPayment;
use App\Contracts\PushSender;
use App\Contracts\TelegramBot;
use App\Enums\PaymentGateway;
use App\Enums\Role;
use App\Enums\SiteFeature;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Push\FakePushSender;
use App\Services\Sms\ArraySmsGateway;
use App\Services\Sms\SmsManager;
use App\Services\Telegram\FakeTelegramBot;
use App\Support\Features;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
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
    ->in('Feature', 'Browser');

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
 * The sign-in code from the last SMS that was sent.
 */
function lastSmsCode(): string
{
    preg_match('/\b(\d{'.(int) config('otp.length').'})\b/', smsGateway()->last()['text'] ?? '', $matches);

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

/**
 * A pending card payment for an order, already registered with the fake test bank.
 *
 * @return array{0: Payment, 1: string} the payment and its fake bank order id
 */
function fakeBankPayment(Order $order): array
{
    $payment = app(StartCardPayment::class)->handle($order, PaymentGateway::Fake);

    return [$payment, (string) $payment->gateway_order_id];
}

/**
 * Posts the fake bank's signed callback for an approved (or declined) payment.
 */
function postFakeCallback(string $orderId, bool $approve = true, ?string $signature = null): TestResponse
{
    $bank = app(PaymentGatewayManager::class)->for(PaymentGateway::Fake);
    assert($bank instanceof FakeGateway);
    $result = $bank->decide($orderId, $approve);

    return test()->call('POST', '/webhooks/payments/fake', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_FAKE_SIGNATURE' => $signature ?? $result['signature'],
    ], $result['body']);
}

/**
 * The recording Telegram bot, bound for this test, with the bot switched on.
 */
function fakeTelegram(): FakeTelegramBot
{
    config(['services.telegram.webhook_secret' => 'test-secret']);
    app()->instance(TelegramBot::class, $bot = new FakeTelegramBot('infokobuleti_test_bot'));
    Features::set(SiteFeature::TelegramBot, true);

    return $bot;
}

/**
 * Posts a Telegram update to the webhook the way Telegram does.
 *
 * @param  array<string, mixed>  $update
 */
function telegramUpdate(array $update): TestResponse
{
    return test()->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->postJson('/webhooks/telegram/test-secret', $update);
}

function fakePush(): FakePushSender
{
    app()->instance(PushSender::class, $sender = new FakePushSender);
    Features::set(SiteFeature::WebPush, true);

    return $sender;
}
