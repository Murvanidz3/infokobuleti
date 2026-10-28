<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentGateway as GatewayName;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidPaymentCallback;
use App\Exceptions\PaymentGatewayError;
use App\Models\Payment;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Bank of Georgia Payments API (https://api.bog.ge/docs/en/payments): OAuth2 client credentials,
 * POST /ecommerce/orders, callbacks signed with SHA256withRSA in the Callback-Signature header,
 * GET /receipt/{id} for the status and POST /payment/refund/{id} for refunds.
 */
class BogGateway implements PaymentGateway
{
    public const string SIGNATURE_HEADER = 'Callback-Signature';

    /**
     * @param  array{client_id: ?string, client_secret: ?string, auth_url: string, api_url: string, public_key: ?string, payment_methods: list<string>, ttl_minutes: int}  $config
     */
    public function __construct(
        private array $config,
        private Cache $cache,
    ) {}

    public function name(): GatewayName
    {
        return GatewayName::Bog;
    }

    public function createOrder(Payment $payment, string $description, string $returnUrl, string $callbackUrl, string $locale): CheckoutSession
    {
        $amount = round((float) $payment->amount, 2);

        $response = $this->send(fn (PendingRequest $http) => $http
            ->withHeaders([
                'Accept-Language' => $locale === 'ka' ? 'ka' : 'en',
                'Idempotency-Key' => (string) $payment->idempotency_key,
            ])
            ->post('/ecommerce/orders', [
                'callback_url' => $callbackUrl,
                'external_order_id' => (string) $payment->getKey(),
                'capture' => 'automatic',
                'ttl' => $this->config['ttl_minutes'],
                'payment_method' => $this->config['payment_methods'],
                'purchase_units' => [
                    'currency' => $payment->currency->value,
                    'total_amount' => $amount,
                    'basket' => [[
                        'product_id' => (string) ($payment->payable_id ?? $payment->getKey()),
                        'description' => mb_substr($description, 0, 120),
                        'quantity' => 1,
                        'unit_price' => $amount,
                    ]],
                ],
                'redirect_urls' => ['success' => $returnUrl, 'fail' => $returnUrl],
            ]));

        $id = $response->json('id');
        $redirect = $response->json('_links.redirect.href');

        if (! is_string($id) || ! is_string($redirect)) {
            throw new PaymentGatewayError('Bank of Georgia returned no order id or payment page.');
        }

        return new CheckoutSession($id, $redirect, (array) $response->json());
    }

    public function verifyCallback(Request $request): CallbackData
    {
        $body = $request->getContent();
        $publicKey = $this->config['public_key'] ?? null;
        $signatureValid = null;

        if (filled($publicKey)) {
            $signature = base64_decode((string) $request->header(self::SIGNATURE_HEADER, ''), true);
            $key = openssl_pkey_get_public($this->pem((string) $publicKey));

            if ($signature === false || $signature === '' || $key === false || openssl_verify($body, $signature, $key, OPENSSL_ALGO_SHA256) !== 1) {
                throw new InvalidPaymentCallback('Bank of Georgia callback signature is not valid.');
            }

            $signatureValid = true;
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($body, true) ?: [];
        $orderId = data_get($payload, 'body.order_id');

        if (! is_string($orderId) || $orderId === '') {
            throw new InvalidPaymentCallback('Bank of Georgia callback without an order id.');
        }

        return new CallbackData($orderId, $payload, $signatureValid);
    }

    public function fetchStatus(Payment $payment): GatewayStatus
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/receipt/'.$payment->gateway_order_id));
        $data = (array) $response->json();

        $status = match ((string) data_get($data, 'order_status.key')) {
            'completed' => PaymentStatus::Paid,
            'rejected' => PaymentStatus::Failed,
            'refunded' => PaymentStatus::Refunded,
            'refunded_partially' => PaymentStatus::PartiallyRefunded,
            default => PaymentStatus::Pending,
        };

        $transferred = data_get($data, 'purchase_units.transfer_amount');
        $refunded = (float) data_get($data, 'purchase_units.refund_amount', 0);

        return new GatewayStatus(
            status: $status,
            paidAmount: is_numeric($transferred) ? (float) $transferred + $refunded : null,
            refundedAmount: $refunded,
            cardMask: data_get($data, 'payment_detail.payer_identifier'),
            raw: $data,
        );
    }

    public function refund(Payment $payment, float $amount): array
    {
        $full = round($amount, 2) >= round((float) $payment->amount - (float) $payment->refunded_amount, 2);

        $response = $this->send(fn (PendingRequest $http) => $http
            ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
            ->post('/payment/refund/'.$payment->gateway_order_id, $full ? [] : ['amount' => round($amount, 2)]));

        return (array) $response->json();
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            $response = $request(
                Http::baseUrl($this->config['api_url'])->withToken($this->token())->acceptJson()->asJson()->timeout(20)->retry(2, 300, throw: false),
            );
        } catch (ConnectionException $e) {
            throw new PaymentGatewayError('Bank of Georgia is not reachable: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new PaymentGatewayError('Bank of Georgia answered '.$response->status().': '.mb_substr($response->body(), 0, 500));
        }

        return $response;
    }

    private function token(): string
    {
        if (blank($this->config['client_id'] ?? null) || blank($this->config['client_secret'] ?? null)) {
            throw new PaymentGatewayError('Bank of Georgia needs BOG_CLIENT_ID and BOG_CLIENT_SECRET.');
        }

        $cached = $this->cache->get('payments.bog.token');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $response = Http::asForm()->withBasicAuth((string) $this->config['client_id'], (string) $this->config['client_secret'])
                ->timeout(15)
                ->post($this->config['auth_url'], ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $e) {
            throw new PaymentGatewayError('Bank of Georgia sign-in is not reachable: '.$e->getMessage(), previous: $e);
        }

        $token = $response->json('access_token');

        if ($response->failed() || ! is_string($token)) {
            throw new PaymentGatewayError('Bank of Georgia refused the API credentials ('.$response->status().').');
        }

        // expires_in is documented in seconds; keep a margin and never cache longer than 50 minutes.
        $ttl = (int) $response->json('expires_in', 300);
        $this->cache->put('payments.bog.token', $token, max(60, min($ttl - 60, 3000)));

        return $token;
    }

    /**
     * Accepts the key with or without the BEGIN/END lines (an .env value is often one line).
     */
    private function pem(string $key): string
    {
        $key = trim(str_replace('\n', "\n", $key));

        if (str_contains($key, 'BEGIN PUBLIC KEY')) {
            return $key;
        }

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(preg_replace('/\s+/', '', $key) ?? '', 64, "\n")."-----END PUBLIC KEY-----\n";
    }
}
