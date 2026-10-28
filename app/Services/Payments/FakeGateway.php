<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentGateway as GatewayName;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidPaymentCallback;
use App\Exceptions\PaymentGatewayError;
use App\Models\Payment;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * A pretend bank for local work and staging. Its payment page (FakeBankController) lets the tester
 * approve or decline; the result comes back as an HMAC-signed callback through the same path as a
 * real bank's. No money moves. Refused in production.
 */
class FakeGateway implements PaymentGateway
{
    public const string SIGNATURE_HEADER = 'X-Fake-Signature';

    private const int TTL_SECONDS = 86400;

    public function __construct(
        private Cache $cache,
        private string $secret,
    ) {}

    public function name(): GatewayName
    {
        return GatewayName::Fake;
    }

    public function createOrder(Payment $payment, string $description, string $returnUrl, string $callbackUrl, string $locale): CheckoutSession
    {
        $orderId = 'fake-'.Str::uuid();

        $this->put($orderId, [
            'status' => 'created',
            'amount' => (float) $payment->amount,
            'refunded' => 0.0,
            'currency' => $payment->currency->value,
            'description' => $description,
            'return_url' => $returnUrl,
            'payment_id' => $payment->getKey(),
        ]);

        $url = URL::temporarySignedRoute('payments.fake.show', now()->addHour(), ['order' => $orderId]);

        return new CheckoutSession($orderId, $url, ['order_id' => $orderId]);
    }

    public function verifyCallback(Request $request): CallbackData
    {
        $body = $request->getContent();
        $signature = (string) $request->header(self::SIGNATURE_HEADER, '');

        if ($signature === '' || ! hash_equals($this->sign($body), $signature)) {
            throw new InvalidPaymentCallback('Fake bank signature mismatch.');
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($body, true) ?: [];

        if (! is_string($payload['order_id'] ?? null)) {
            throw new InvalidPaymentCallback('Fake bank callback without an order id.');
        }

        return new CallbackData($payload['order_id'], $payload, true);
    }

    public function fetchStatus(Payment $payment): GatewayStatus
    {
        $order = $this->order((string) $payment->gateway_order_id);

        $status = match ($order['status']) {
            'paid' => PaymentStatus::Paid,
            'declined' => PaymentStatus::Failed,
            'refunded' => PaymentStatus::Refunded,
            'partially_refunded' => PaymentStatus::PartiallyRefunded,
            default => PaymentStatus::Pending,
        };

        return new GatewayStatus(
            status: $status,
            paidAmount: $status === PaymentStatus::Pending || $status === PaymentStatus::Failed ? null : (float) $order['amount'],
            refundedAmount: (float) $order['refunded'],
            cardMask: $status === PaymentStatus::Pending ? null : '400000xxxxxx0002',
            raw: $order,
        );
    }

    public function refund(Payment $payment, float $amount): array
    {
        $orderId = (string) $payment->gateway_order_id;
        $order = $this->order($orderId);

        if (! in_array($order['status'], ['paid', 'partially_refunded'], true)) {
            throw new PaymentGatewayError('Only paid fake orders can be refunded.');
        }

        $order['refunded'] = round((float) $order['refunded'] + $amount, 2);
        $order['status'] = $order['refunded'] >= (float) $order['amount'] ? 'refunded' : 'partially_refunded';
        $this->put($orderId, $order);

        return ['key' => 'request_received', 'amount' => $amount];
    }

    /**
     * The tester's choice on the fake payment page. Returns the signed callback body to deliver.
     *
     * @return array{body: string, signature: string, return_url: string}
     */
    public function decide(string $orderId, bool $approve): array
    {
        $order = $this->order($orderId);

        if ($order['status'] === 'created') {
            $order['status'] = $approve ? 'paid' : 'declined';
            $this->put($orderId, $order);
        }

        $body = (string) json_encode(['event' => 'order_payment', 'order_id' => $orderId, 'status' => $order['status']]);

        return ['body' => $body, 'signature' => $this->sign($body), 'return_url' => (string) $order['return_url']];
    }

    /**
     * @return array<string, mixed>
     */
    public function order(string $orderId): array
    {
        $order = $this->cache->get('fake-bank:'.$orderId);

        if (! is_array($order)) {
            throw new PaymentGatewayError("Unknown fake bank order [{$orderId}].");
        }

        return $order;
    }

    public function sign(string $body): string
    {
        return hash_hmac('sha256', $body, $this->secret);
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function put(string $orderId, array $order): void
    {
        $this->cache->put('fake-bank:'.$orderId, $order, self::TTL_SECONDS);
    }
}
