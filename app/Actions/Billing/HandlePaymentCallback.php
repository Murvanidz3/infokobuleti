<?php

namespace App\Actions\Billing;

use App\Enums\PaymentEventType;
use App\Enums\PaymentGateway as GatewayName;
use App\Exceptions\InvalidPaymentCallback;
use App\Exceptions\PaymentGatewayError;
use App\Models\Payment;
use App\Services\Billing\PaymentLog;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\Request;

/**
 * A bank's server-to-server notification: logged as received, verified (signature / sender),
 * then the status is read back from the bank and applied idempotently.
 */
class HandlePaymentCallback
{
    public function __construct(
        private PaymentGatewayManager $gateways,
        private PaymentLog $log,
        private SyncPaymentStatus $sync,
    ) {}

    /**
     * @throws InvalidPaymentCallback
     * @throws PaymentGatewayError
     */
    public function handle(GatewayName $gateway, Request $request): ?Payment
    {
        $raw = json_decode($request->getContent(), true);
        $payload = is_array($raw) ? $raw : $request->all();

        try {
            $callback = $this->gateways->for($gateway)->verifyCallback($request);
        } catch (InvalidPaymentCallback $e) {
            $this->log->record(PaymentEventType::Callback, $gateway, null, ['error' => $e->getMessage(), 'body' => $payload], signatureValid: false, ip: $request->ip());

            throw $e;
        }

        $payment = Payment::query()
            ->where('gateway', $gateway->value)
            ->where('gateway_order_id', $callback->gatewayOrderId)
            ->first();

        $this->log->record(PaymentEventType::Callback, $gateway, $payment, $callback->payload, signatureValid: $callback->signatureValid, ip: $request->ip());

        if ($payment === null) {
            return null;
        }

        return $this->sync->handle($payment);
    }
}
