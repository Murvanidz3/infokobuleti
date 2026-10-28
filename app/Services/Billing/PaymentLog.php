<?php

namespace App\Services\Billing;

use App\Enums\PaymentEventType;
use App\Enums\PaymentGateway as GatewayName;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Support\Arr;

/**
 * Writes the payment log. Card numbers arrive masked from the banks; tokens never reach it.
 */
class PaymentLog
{
    private const array SECRET_KEYS = ['access_token', 'client_secret', 'token', 'password', 'authorization'];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(PaymentEventType $type, GatewayName $gateway, ?Payment $payment = null, array $payload = [], ?string $status = null, ?bool $signatureValid = null, ?string $ip = null): PaymentEvent
    {
        return PaymentEvent::query()->create([
            'payment_id' => $payment?->getKey(),
            'gateway' => $gateway->value,
            'type' => $type,
            'status' => $status,
            'signature_valid' => $signatureValid,
            'payload' => $this->clean($payload),
            'ip' => $ip,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function clean(array $payload): array
    {
        foreach (Arr::dot($payload) as $key => $value) {
            $last = strtolower((string) last(explode('.', (string) $key)));

            if (in_array($last, self::SECRET_KEYS, true)) {
                Arr::set($payload, (string) $key, '***');
            }
        }

        return $payload;
    }
}
