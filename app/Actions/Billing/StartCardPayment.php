<?php

namespace App\Actions\Billing;

use App\Enums\PaymentEventType;
use App\Enums\PaymentGateway as GatewayName;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayError;
use App\Exceptions\PurchaseNotAllowed;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Billing\PaymentLog;
use App\Services\Payments\PaymentGatewayManager;

/**
 * Sends an order to the bank: creates the payment row, registers it with the gateway and returns
 * the payment with the bank page URL the payer must be redirected to.
 */
class StartCardPayment
{
    public function __construct(
        private PaymentGatewayManager $gateways,
        private PaymentLog $log,
    ) {}

    /**
     * @throws PaymentGatewayError
     * @throws PurchaseNotAllowed
     */
    public function handle(Order $order, GatewayName $gateway): Payment
    {
        if (! $order->status->isOpen() || ! $this->gateways->isEnabled($gateway)) {
            throw new PurchaseNotAllowed;
        }

        $order->forceFill(['method' => PaymentMethod::Card])->save();

        $payment = new Payment([
            'user_id' => $order->user_id,
            'gateway' => $gateway,
            'amount' => $order->amount,
            'currency' => $order->currency,
        ]);
        $payment->payable()->associate($order);
        $payment->save();

        $order->loadMissing(['user', 'pricePlan']);
        $locale = $order->user->preferredLocale();

        try {
            $session = $this->gateways->for($gateway)->createOrder(
                $payment,
                $order->title($locale),
                localized_route('payments.return', ['payment' => $payment->getKey()], $locale),
                route('webhooks.payments', ['gateway' => $gateway->value]),
                $locale,
            );
        } catch (PaymentGatewayError $e) {
            $payment->forceFill(['status' => PaymentStatus::Failed])->save();
            $this->log->record(PaymentEventType::Error, $gateway, $payment, ['message' => $e->getMessage()]);

            throw $e;
        }

        $payment->forceFill([
            'gateway_order_id' => $session->gatewayOrderId,
            'redirect_url' => $session->redirectUrl,
        ])->save();

        $this->log->record(PaymentEventType::Created, $gateway, $payment, $session->raw, PaymentStatus::Pending->value);

        return $payment;
    }
}
