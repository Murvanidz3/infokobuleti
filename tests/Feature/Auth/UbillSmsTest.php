<?php

use App\Enums\SmsStatus;
use App\Exceptions\SmsDeliveryFailed;
use App\Models\SmsMessage;
use App\Services\Monitoring\HealthChecks;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsOptions;
use App\Services\Sms\SmsSender;
use App\Services\Sms\UbillSmsGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
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

/**
 * UBill answers as recorded from the live API (brand names under "brands", integer flags).
 */
function fakeUbill(array $send = ['statusID' => 0, 'smsID' => 117345, 'message' => 'SMS Sent'], int $balance = 5000, array $brands = []): void
{
    Http::fake([
        'api.ubill.dev/v1/sms/brandNames*' => Http::response(['statusID' => 0, 'brands' => $brands ?: [
            ['id' => 1, 'name' => 'test', 'authorized' => 1, 'createdAt' => '2025-01-23 20:30:21'],
            ['id' => 5, 'name' => 'KOBULETI', 'authorized' => 0, 'createdAt' => '2026-10-20 10:00:00'],
            ['id' => 7, 'name' => 'KOBULETI', 'authorized' => 1, 'createdAt' => '2026-10-27 10:00:00'],
        ]]),
        'api.ubill.dev/v1/sms/send*' => Http::response($send),
        'api.ubill.dev/v1/sms/balance*' => Http::response(['statusID' => 0, 'sms' => $balance]),
        'api.ubill.dev/v1/sms/report/*' => Http::response(['statusID' => 0, 'result' => [['number' => '995555123456', 'statusID' => 1]]]),
    ]);
}

it('sends sign-in codes as a POST with the key in a header and the approved brand', function () {
    fakeUbill();

    $id = app(SmsManager::class)->driver('ubill')->send('+995555123456', 'კოდი: 123456', SmsOptions::otp());

    expect($id)->toBe('117345');
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://api.ubill.dev/v1/sms/send'
        && $request->header('key') === ['secret-key']
        && ! str_contains($request->url(), 'secret-key')
        && $request['brandID'] === 7
        && $request['numbers'] === ['995555123456']
        && $request['text'] === 'კოდი: 123456'
        && $request['otp'] === true
        && $request['stopList'] === false);
});

it('reads the documented brand list shape too', function () {
    Http::fake([
        'api.ubill.dev/v1/sms/brandNames*' => Http::response(['statusID' => 0, 'data' => [['id' => '3', 'name' => 'KOBULETI', 'authorized' => '1']]]),
        'api.ubill.dev/v1/sms/send*' => Http::response(['statusID' => 0, 'smsID' => '9']),
    ]);

    app(SmsManager::class)->driver('ubill')->send('+995555123456', 'x');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/send') && $request['brandID'] === 3);
});

it('sends service messages without the OTP flag', function () {
    fakeUbill();

    app(SmsManager::class)->driver('ubill')->send('+995555123456', 'Hello');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/send') && ! isset($request['otp']));
});

it('sends a batch as a plain list, or with a text per number', function () {
    fakeUbill();
    /** @var UbillSmsGateway $gateway */
    $gateway = app(SmsManager::class)->driver('ubill');

    $gateway->sendBatch([['phone' => '+995555111111', 'text' => 'Hi'], ['phone' => '+995555222222', 'text' => 'Hi']], SmsOptions::campaign());
    $gateway->sendBatch([['phone' => '+995555111111', 'text' => 'Hi Nika'], ['phone' => '+995555222222', 'text' => 'Hi Ana']], SmsOptions::campaign(false));

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/send') && $request['numbers'] === ['995555111111', '995555222222'] && $request['text'] === 'Hi' && $request['stopList'] === true);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/send') && ! isset($request['text']) && $request['stopList'] === false
        && $request['numbers'] === [['number' => '995555111111', 'text' => 'Hi Nika'], ['number' => '995555222222', 'text' => 'Hi Ana']]);
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

it('fails when the sender brand is not approved yet', function () {
    fakeUbill(brands: [['id' => 5, 'name' => 'KOBULETI', 'authorized' => 0]]);

    app(SmsManager::class)->driver('ubill')->send('+995555123456', 'x');
})->throws(SmsDeliveryFailed::class, 'no approved brand name "KOBULETI"');

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

it('sends a queued sign-in SMS end to end with the OTP flag and stores the UBill id', function () {
    fakeUbill();

    app(SmsSender::class)->send('+995555123456', 'otp.login', 'კოდი: 654321');

    $message = SmsMessage::query()->sole();
    expect($message)->driver->toBe('ubill')->status->toBe(SmsStatus::Sent)->provider_message_id->toBe('117345')->body->toBeNull();
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/send') && $request['otp'] === true);
});

it('records UBill delivery reports with the callback key only', function () {
    $message = SmsMessage::query()->create(['phone' => '+995555123456', 'purpose' => 'otp.login', 'driver' => 'ubill', 'status' => SmsStatus::Sent, 'provider_message_id' => '117345']);
    $query = ['event' => 'sms.delivery.updated', 'smsID' => '117345', 'number' => '995555123456', 'statusID' => 1, 'date' => '2026-10-27 12:00:00'];

    $this->get('/webhooks/sms/ubill?'.http_build_query($query + ['key' => 'forged']))->assertForbidden();
    $this->get('/webhooks/sms/ubill?'.http_build_query($query))->assertForbidden();
    expect($message->fresh()->status)->toBe(SmsStatus::Sent);

    $this->get('/webhooks/sms/ubill?'.http_build_query($query + ['key' => 'callback-secret']))->assertNoContent();
    expect($message->fresh())->status->toBe(SmsStatus::Delivered)->delivered_at->not->toBeNull();

    // A late "error" report never overrides a delivered message.
    $this->get('/webhooks/sms/ubill?'.http_build_query(['statusID' => 4, 'key' => 'callback-secret'] + $query))->assertNoContent();
    expect($message->fresh()->status)->toBe(SmsStatus::Delivered);
});

it('matches batch callbacks by number', function () {
    $first = SmsMessage::query()->create(['phone' => '+995555111111', 'purpose' => 'campaign', 'driver' => 'ubill', 'status' => SmsStatus::Sent, 'provider_message_id' => '5']);
    $second = SmsMessage::query()->create(['phone' => '+995555222222', 'purpose' => 'campaign', 'driver' => 'ubill', 'status' => SmsStatus::Sent, 'provider_message_id' => '5']);

    $this->get('/webhooks/sms/ubill?smsID=5&number=995555222222&statusID=2&key=callback-secret')->assertNoContent();

    expect($first->fresh()->status)->toBe(SmsStatus::Sent)
        ->and($second->fresh())->status->toBe(SmsStatus::Failed)->error->toBe('Provider status 2');
});

it('forgets the cached brand when UBill approves a sender name', function () {
    fakeUbill();
    app(SmsManager::class)->driver('ubill')->send('+995555123456', 'a');
    expect(Cache::get('sms:ubill:brand:'.md5('secret-key|kobuleti')))->toBe(7);

    $this->get('/webhooks/sms/ubill?event=sms.brand.activated&brandID=7&brandName=KOBULETI&authorized=1&key=callback-secret')->assertNoContent();

    expect(Cache::has('sms:ubill:brand:'.md5('secret-key|kobuleti')))->toBeFalse();
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

it('fetches delivery reports of recent messages', function () {
    fakeUbill();
    $message = SmsMessage::query()->create(['phone' => '+995555123456', 'purpose' => 'admin.single', 'driver' => 'ubill', 'status' => SmsStatus::Sent, 'provider_message_id' => '117345', 'sent_at' => now()->subMinutes(5)]);

    $this->artisan('sms:sync-reports')->expectsOutputToContain('1 message(s) updated')->assertSuccessful();

    expect($message->fresh())->status->toBe(SmsStatus::Delivered)->checked_at->not->toBeNull();
});

it('shows the UBill account from the console and sends a test SMS', function () {
    fakeUbill();

    $this->artisan('sms:ubill-status', ['--send' => '995555123456', '--report' => '117345'])
        ->expectsOutputToContain('brandID 7')
        ->expectsOutputToContain('SMS balance: 5000')
        ->expectsOutputToContain('555123456 status 1')
        ->expectsOutputToContain('smsID 117345')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/send') && ! isset($request['otp']));
});
