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
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * TBC Bank Checkout / tpay API (https://developers.tbcbank.ge): access token from
 * /tpay/access-token, POST /tpay/payments, GET /tpay/payments/{payId}, POST .../cancel for refunds.
 * TBC callbacks carry only the PaymentId and are not signed: the sender address is checked and the
 * status is always read back from the API.
 */
class TbcGateway implements PaymentGateway
{
    /**
     * @param  array{api_key: ?string, client_id: ?string, client_secret: ?string, api_url: string, callback_ips: list<string>, expiration_minutes: int}  $config
     */
    public function __construct(
        private array $config,
        private Cache $cache,
    ) {}

    public function name(): GatewayName
    {
        return GatewayName::Tbc;
    }

    public function createOrder(Payment $payment, string $description, string $returnUrl, string $callbackUrl, string $locale): CheckoutSession
    {
        $response = $this->send(fn (PendingRequest $http) => $http->post('/tpay/payments', [
            'amount' => ['currency' => $payment->currency->value, 'total' => round((float) $payment->amount, 2)],
            'returnurl' => $returnUrl,
            'callbackUrl' => $callbackUrl,
            'merchantPaymentId' => (string) $payment->getKey(),
            'extra' => Str::limit(Str::ascii('infokobuleti '.$payment->getKey()), 25, ''),
            'description' => Str::limit(Str::ascii($description), 30, ''),
            'expirationMinutes' => $this->config['expiration_minutes'],
            'language' => $locale === 'ka' ? 'KA' : 'EN',
        ]));

        $payId = $response->json('payId');
        $redirect = collect((array) $response->json('links'))->firstWhere('rel', 'approval_url')['uri'] ?? null;

        if (! is_string($payId) || ! is_string($redirect)) {
            throw new PaymentGatewayError('TBC returned no payment id or approval page.');
        }

        return new CheckoutSession($payId, $redirect, (array) $response->json());
    }

    public function verifyCallback(Request $request): CallbackData
    {
        $allowed = $this->config['callback_ips'];

        if ($allowed !== [] && ! IpUtils::checkIp((string) $request->ip(), $allowed)) {
            throw new InvalidPaymentCallback('TBC callback from an unknown address.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all() ?: $request->all();
        $payId = $payload['PaymentId'] ?? $payload['paymentId'] ?? null;

        if (! is_string($payId) || $payId === '') {
            throw new InvalidPaymentCallback('TBC callback without a PaymentId.');
        }

        return new CallbackData($payId, $payload, null);
    }

    public function fetchStatus(Payment $payment): GatewayStatus
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/tpay/payments/'.$payment->gateway_order_id));
        $data = (array) $response->json();

        $status = match ((string) ($data['status'] ?? '')) {
            'Succeeded' => PaymentStatus::Paid,
            'Failed', 'Expired' => PaymentStatus::Failed,
            'Returned' => PaymentStatus::Refunded,
            'PartialReturned' => PaymentStatus::PartiallyRefunded,
            default => PaymentStatus::Pending,
        };

        return new GatewayStatus(
            status: $status,
            paidAmount: in_array($status, [PaymentStatus::Pending, PaymentStatus::Failed], true) ? null : (float) ($data['amount'] ?? 0),
            refundedAmount: (float) ($data['returnedAmount'] ?? 0),
            cardMask: isset($data['paymentCardNumber']) ? (string) $data['paymentCardNumber'] : null,
            raw: $data,
        );
    }

    public function refund(Payment $payment, float $amount): array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->post('/tpay/payments/'.$payment->gateway_order_id.'/cancel', [
            'amount' => round($amount, 2),
        ]));

        return (array) $response->json();
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            $response = $request(
                Http::baseUrl($this->config['api_url'])
                    ->withHeaders(['apikey' => (string) $this->config['api_key']])
                    ->withToken($this->token())
                    ->acceptJson()->asJson()->timeout(20),
            );
        } catch (ConnectionException $e) {
            throw new PaymentGatewayError('TBC is not reachable: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new PaymentGatewayError('TBC answered '.$response->status().': '.mb_substr($response->body(), 0, 500));
        }

        return $response;
    }

    private function token(): string
    {
        foreach (['api_key' => 'TBC_API_KEY', 'client_id' => 'TBC_CLIENT_ID', 'client_secret' => 'TBC_CLIENT_SECRET'] as $key => $env) {
            if (blank($this->config[$key] ?? null)) {
                throw new PaymentGatewayError("TBC needs {$env}.");
            }
        }

        $cached = $this->cache->get('payments.tbc.token');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $response = Http::asForm()->withHeaders(['apikey' => (string) $this->config['api_key']])->timeout(15)
                ->post(rtrim($this->config['api_url'], '/').'/tpay/access-token', [
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                ]);
        } catch (ConnectionException $e) {
            throw new PaymentGatewayError('TBC sign-in is not reachable: '.$e->getMessage(), previous: $e);
        }

        $token = $response->json('access_token');

        if ($response->failed() || ! is_string($token)) {
            throw new PaymentGatewayError('TBC refused the API credentials ('.$response->status().').');
        }

        $ttl = (int) $response->json('expires_in', 300);
        $this->cache->put('payments.tbc.token', $token, max(60, min($ttl - 60, 3000)));

        return $token;
    }
}
