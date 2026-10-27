<?php

use App\Enums\SmsStatus;
use App\Exceptions\SmsDeliveryFailed;
use App\Jobs\SendSms;
use App\Models\SmsMessage;
use App\Services\Monitoring\HealthChecks;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsSender;
use App\Services\Sms\UbillSmsGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'sms.default' => 'ubill',
        'sms.sender' => 'KOBULETI',
        'sms.drivers.ubill' => [
            'api_key' => 'secret-key',
            'brand_id' => null,
            'brand_name' => 'KOBULETI',
            'callback_key' => 'callback-secret',
            'base_url' => 'https://api.ubill.dev/v1/sms',
            'min_balance' => 200,
        ],
    ]);
});

function fakeUbill(array $send = ['statusID' => 0, 'smsID' => '117345', 'message' => 'SMS Sent'], int $balance = 5000): void
{
    Http::fake([
        'api.ubill.dev/v1/sms/brandNames*' => Http::response(['statusID' => 0, 'data' => [
            ['id' => '1', 'name' => 'test', 'authorized' => '1'],
            ['id' => '7', 'name' => 'KOBULETI', 'authorized' => '1'],
        ]]),
        'api.ubill.dev/v1/sms/send*' => Http::response($send),
        'api.ubill.dev/v1/sms/balance*' => Http::response(['statusID' => 0, 'sms' => $balance]),
        'api.ubill.dev/v1/sms/report/*' => Http::response(['statusID' => 0, 'result' => [['number' => '995555123456', 'statusID' => 1]]]),
    ]);
}

it('sends sign-in codes through UBill with the brand looked up by name', function () {
    fakeUbill();

    $id = app(SmsManager::class)->driver('ubill')->send('+995555123456', 'კოდი: 123456');

    expect($id)->toBe('117345');
    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.ubill.dev/v1/sms/send?')
        && $request['key'] === 'secret-key'
        && $request['brandID'] === 7
        && $request['numbers'] === '995555123456'
        && $request['text'] === 'კოდი: 123456'
        && $request['otp'] === 'true'
        && $request['stopList'] === 'false');
});

it('caches the brand id and uses a configured one without a lookup', function () {
    fakeUbill();
    $gateway = app(SmsManager::class)->driver('ubill');
    $gateway->send('+995555123456', 'a');
    $gateway->send('+995555123456', 'b');

    Http::assertSentCount(3);

    config(['sms.drivers.ubill.brand_id' => '9']);
    Http::fake(['api.ubill.dev/v1/sms/send*' => Http::response(['statusID' => 0, 'smsID' => '1'])]);
    app()->forgetInstance(SmsManager::class);
    app(SmsManager::class)->driver('ubill')->send('+995555123456', 'c');

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/send') && $request['brandID'] === 9);
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'brandNames'));
});

it('turns UBill error codes into failed deliveries without leaking the key', function () {
    fakeUbill(['statusID' => 40, 'message' => '']);

    try {
        app(SmsManager::class)->driver('ubill')->send('+995555123456', 'x');
        $this->fail('No exception');
    } catch (SmsDeliveryFailed $exception) {
        expect($exception->getMessage())->toBe('UBill error 40: not enough SMS on the balance')->not->toContain('secret-key');
    }
});

it('fails when the sender brand is not authorized on the account', function () {
    config(['sms.drivers.ubill.brand_name' => 'OTHER']);
    fakeUbill();

    app(SmsManager::class)->driver('ubill')->send('+995555123456', 'x');
})->throws(SmsDeliveryFailed::class, 'no authorized brand name "OTHER"');

it('needs an API key', function () {
    config(['sms.drivers.ubill.api_key' => null]);

    app(SmsManager::class)->driver('ubill');
})->throws(InvalidArgumentException::class, 'UBILL_API_KEY');

it('formats numbers for UBill', function (string $phone, string $expected) {
    expect(UbillSmsGateway::number($phone))->toBe($expected);
})->with([
    ['+995 555 12 34 56', '995555123456'],
    ['00995555123456', '995555123456'],
    ['995555123456', '995555123456'],
]);

it('sends a queued sign-in SMS end to end and stores the UBill id', function () {
    fakeUbill();

    app(SmsSender::class)->send('+995555123456', 'otp.login', 'კოდი: 654321');

    $message = SmsMessage::query()->sole();
    expect($message)->driver->toBe('ubill')->status->toBe(SmsStatus::Sent)->provider_message_id->toBe('117345');
    expect(SendSms::class)->toBeString();
});

it('records UBill delivery reports with the callback key only', function () {
    $message = SmsMessage::query()->create(['phone' => '+995555123456', 'purpose' => 'otp.login', 'driver' => 'ubill', 'status' => SmsStatus::Sent, 'provider_message_id' => '117345']);
    $query = ['smsID' => '117345', 'number' => '995555123456', 'statusID' => 1, 'date' => '2026-10-27 12:00:00'];

    $this->get('/webhooks/sms/ubill?'.http_build_query($query + ['key' => 'forged']))->assertForbidden();
    $this->get('/webhooks/sms/ubill?'.http_build_query($query))->assertForbidden();
    expect($message->fresh()->status)->toBe(SmsStatus::Sent);

    $this->get('/webhooks/sms/ubill?'.http_build_query($query + ['key' => 'callback-secret']))->assertNoContent();
    expect($message->fresh())->status->toBe(SmsStatus::Delivered)->delivered_at->not->toBeNull();

    // A late "pending" or "error" report never overrides a delivered message.
    $this->get('/webhooks/sms/ubill?'.http_build_query(['statusID' => 4, 'key' => 'callback-secret'] + $query))->assertNoContent();
    expect($message->fresh()->status)->toBe(SmsStatus::Delivered);
});

it('marks undelivered UBill messages as failed', function () {
    $message = SmsMessage::query()->create(['phone' => '+995555123456', 'purpose' => 'otp.login', 'driver' => 'ubill', 'status' => SmsStatus::Sent, 'provider_message_id' => '5']);

    $this->get('/webhooks/sms/ubill?smsID=5&statusID=2&key=callback-secret')->assertNoContent();

    expect($message->fresh())->status->toBe(SmsStatus::Failed)->error->toBe('UBill status 2');
});

it('refuses UBill callbacks when no callback key is configured', function () {
    config(['sms.drivers.ubill.callback_key' => null]);

    $this->get('/webhooks/sms/ubill?smsID=5&statusID=1&key=')->assertForbidden();
});

it('watches the UBill balance in the health checks', function (int $balance, bool $ok) {
    fakeUbill(balance: $balance);

    $check = collect(app(HealthChecks::class)->run())->firstWhere('key', 'sms');

    expect($check)->ok->toBe($ok)->value->toBe("{$balance} SMS");
})->with([[5000, true], [50, false]]);

it('shows the UBill account from the console and sends a test SMS', function () {
    fakeUbill();

    $this->artisan('sms:ubill-status', ['--send' => '995555123456', '--report' => '117345'])
        ->expectsOutputToContain('brandID 7')
        ->expectsOutputToContain('SMS balance: 5000')
        ->expectsOutputToContain('995555123456 status 1')
        ->expectsOutputToContain('smsID 117345')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/send') && ! isset($request['otp']));
});
