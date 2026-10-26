<?php

use App\Actions\Billing\StartCardPayment;
use App\Actions\Billing\SyncPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentEventType;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\PricePlanProduct;
use App\Enums\WalletEntryType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PricePlan;
use App\Models\Promotion;
use App\Models\Property;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\PaymentReceipt;
use App\Services\Billing\PaymentLog;
use App\Services\Payments\GatewayStatus;
use App\Services\Payments\PaymentGatewayManager;
use Database\Seeders\PricePlanSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed(PricePlanSeeder::class);
    Notification::fake();
    $this->user = User::factory()->create();
});

function vipOrder(User $user, string $code = 'vip-7'): Order
{
    $plan = PricePlan::query()->where('code', $code)->firstOrFail();
    $listing = Property::factory()->published()->for($user)->create();

    return Order::factory()->for($user)->forPlan($plan, $listing)->create();
}

it('activates the purchase when the fake bank sends a correctly signed callback', function () {
    $order = vipOrder($this->user);
    [$payment, $bankId] = fakeBankPayment($order);

    postFakeCallback($bankId)->assertOk();

    $order->refresh();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Fulfilled)
        ->and($order->number)->toMatch('/^IK-\d{4}-\d{6}$/')
        ->and($order->purchasable->fresh()->promotion_level)->toBe(1);

    Notification::assertSentTo($this->user, PaymentReceipt::class);
    expect(PaymentEvent::query()->where('payment_id', $payment->id)->where('type', PaymentEventType::Callback)->value('signature_valid'))->toBeTrue();
});

it('rejects a callback with a wrong signature and changes nothing', function () {
    $order = vipOrder($this->user);
    [$payment, $bankId] = fakeBankPayment($order);

    postFakeCallback($bankId, signature: str_repeat('0', 64))->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(Promotion::query()->count())->toBe(0)
        ->and(PaymentEvent::query()->where('signature_valid', false)->exists())->toBeTrue();
});

it('handles the same callback twice only once', function () {
    $order = Order::factory()->for($this->user)->create(['product' => PricePlanProduct::WalletTopUp, 'amount' => 50]);
    [, $bankId] = fakeBankPayment($order);

    postFakeCallback($bankId)->assertOk();
    postFakeCallback($bankId)->assertOk();
    postFakeCallback($bankId)->assertOk();

    expect((float) $this->user->fresh()->balance)->toBe(50.0)
        ->and(WalletTransaction::query()->where('type', WalletEntryType::TopUp)->count())->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::Fulfilled);

    Notification::assertSentToTimes($this->user, PaymentReceipt::class, 1);
});

it('keeps the order open when the bank declines the payment', function () {
    $order = vipOrder($this->user);
    [$payment, $bankId] = fakeBankPayment($order);

    postFakeCallback($bankId, approve: false)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(Promotion::query()->count())->toBe(0);
});

it('answers 200 for an unknown order and 404 for a gateway that is switched off', function () {
    $bank = app(PaymentGatewayManager::class)->for(PaymentGateway::Fake);
    $body = json_encode(['order_id' => 'fake-unknown']);

    $this->call('POST', '/webhooks/payments/fake', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_FAKE_SIGNATURE' => $bank->sign($body)], $body)->assertOk();
    $this->postJson('/webhooks/payments/bog', [])->assertNotFound();
    $this->postJson('/webhooks/payments/paypal', [])->assertNotFound();
});

it('never activates an order when the bank reports a different paid amount', function () {
    $order = vipOrder($this->user);
    [$payment] = fakeBankPayment($order);

    app(SyncPaymentStatus::class)->apply($payment, new GatewayStatus(PaymentStatus::Paid, paidAmount: 0.5));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('credits the wallet when the same order is paid twice', function () {
    $order = vipOrder($this->user);
    [, $first] = fakeBankPayment($order);
    [, $second] = fakeBankPayment($order->fresh()->forceFill(['status' => OrderStatus::Pending]));

    postFakeCallback($first)->assertOk();
    postFakeCallback($second)->assertOk();

    expect(Promotion::query()->count())->toBe(1)
        ->and((float) $this->user->fresh()->balance)->toBe(10.0)
        ->and(WalletTransaction::query()->where('type', WalletEntryType::Refund)->value('note'))->toBe('duplicate_payment');
});

describe('Bank of Georgia', function () {
    beforeEach(function () {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $private);
        $this->privateKey = $private;

        config([
            'payments.gateways' => ['bog'],
            'payments.drivers.bog.client_id' => 'client',
            'payments.drivers.bog.client_secret' => 'secret',
            'payments.drivers.bog.public_key' => openssl_pkey_get_details($key)['key'],
        ]);

        Http::fake([
            'oauth2.bog.ge/*' => Http::response(['access_token' => 'token-123', 'token_type' => 'Bearer', 'expires_in' => 3600]),
            'api.bog.ge/payments/v1/ecommerce/orders' => Http::response(['id' => 'bog-order-1', '_links' => ['redirect' => ['href' => 'https://payment.bog.ge/?order_id=bog-order-1']]]),
            'api.bog.ge/payments/v1/receipt/bog-order-1' => Http::response([
                'order_id' => 'bog-order-1',
                'order_status' => ['key' => 'completed'],
                'purchase_units' => ['transfer_amount' => '10.00', 'refund_amount' => '0', 'currency_code' => 'GEL'],
                'payment_detail' => ['payer_identifier' => '548888xxxxxx9893'],
            ]),
        ]);
    });

    function bogCallback(string $body, string $privateKey): TestResponse
    {
        openssl_sign($body, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return test()->call('POST', '/webhooks/payments/bog', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CALLBACK_SIGNATURE' => base64_encode($signature),
        ], $body);
    }

    it('creates the order at the bank and accepts its RSA-signed callback', function () {
        $order = vipOrder($this->user);
        $payment = app(StartCardPayment::class)->handle($order, PaymentGateway::Bog);

        expect($payment->gateway_order_id)->toBe('bog-order-1')
            ->and($payment->redirect_url)->toBe('https://payment.bog.ge/?order_id=bog-order-1');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/ecommerce/orders')
            && $request->hasHeader('Idempotency-Key', (string) $payment->idempotency_key)
            && $request['purchase_units']['total_amount'] === 10.0
            && str_ends_with($request['callback_url'], '/webhooks/payments/bog'));

        bogCallback(json_encode(['event' => 'order_payment', 'body' => ['order_id' => 'bog-order-1']]), $this->privateKey)->assertOk();

        expect($payment->fresh())->status->toBe(PaymentStatus::Paid)
            ->and($payment->fresh()->payload['card'])->toBe('548888xxxxxx9893')
            ->and($order->fresh()->status)->toBe(OrderStatus::Fulfilled);
    });

    it('refuses a callback whose body was changed after signing', function () {
        $order = vipOrder($this->user);
        $payment = app(StartCardPayment::class)->handle($order, PaymentGateway::Bog);
        $signed = json_encode(['event' => 'order_payment', 'body' => ['order_id' => 'bog-order-1']]);
        openssl_sign($signed, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        $this->call('POST', '/webhooks/payments/bog', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CALLBACK_SIGNATURE' => base64_encode($signature),
        ], str_replace('bog-order-1', 'bog-order-2', $signed))->assertForbidden();

        $this->call('POST', '/webhooks/payments/bog', [], [], [], ['CONTENT_TYPE' => 'application/json'], $signed)->assertForbidden();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    });
});

describe('TBC Bank', function () {
    beforeEach(function () {
        config([
            'payments.gateways' => ['tbc'],
            'payments.drivers.tbc.api_key' => 'key',
            'payments.drivers.tbc.client_id' => 'client',
            'payments.drivers.tbc.client_secret' => 'secret',
        ]);

        Http::fake([
            'api.tbcbank.ge/v1/tpay/access-token' => Http::response(['access_token' => 'tbc-token', 'expires_in' => 86400]),
            'api.tbcbank.ge/v1/tpay/payments' => Http::response(['payId' => 'tbc-pay-1', 'status' => 'Created', 'links' => [['uri' => 'https://ecommerce.ufc.ge/pay/tbc-pay-1', 'method' => 'REDIRECT', 'rel' => 'approval_url']]]),
            'api.tbcbank.ge/v1/tpay/payments/tbc-pay-1' => Http::response(['payId' => 'tbc-pay-1', 'status' => 'Succeeded', 'amount' => 10.0, 'returnedAmount' => 0]),
        ]);
    });

    it('accepts callbacks only from the bank addresses and reads the status back from the API', function () {
        $order = vipOrder($this->user);
        $payment = app(StartCardPayment::class)->handle($order, PaymentGateway::Tbc);

        expect($payment->redirect_url)->toBe('https://ecommerce.ufc.ge/pay/tbc-pay-1');

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])->postJson('/webhooks/payments/tbc', ['PaymentId' => 'tbc-pay-1'])->assertForbidden();
        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);

        $this->withServerVariables(['REMOTE_ADDR' => '193.104.20.44'])->postJson('/webhooks/payments/tbc', ['PaymentId' => 'tbc-pay-1'])->assertOk();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
            ->and($order->fresh()->status)->toBe(OrderStatus::Fulfilled);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.tbcbank.ge/v1/tpay/payments/tbc-pay-1' && $request->hasHeader('apikey', 'key'));
    });
});

it('keeps tokens out of the payment log', function () {
    $log = app(PaymentLog::class)->record(PaymentEventType::Created, PaymentGateway::Fake, Payment::factory()->create(), ['access_token' => 'abc', 'nested' => ['client_secret' => 'x', 'id' => 5]]);

    expect($log->payload)->toBe(['access_token' => '***', 'nested' => ['client_secret' => '***', 'id' => 5]]);
});
