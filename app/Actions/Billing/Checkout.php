<?php

namespace App\Actions\Billing;

use App\Enums\PaymentGateway as GatewayName;
use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientBalance;
use App\Exceptions\PaymentGatewayError;
use App\Exceptions\PurchaseNotAllowed;
use App\Models\Order;
use App\Services\Payments\PaymentGatewayManager;

/**
 * Pays an open order the chosen way. Returns the bank page to redirect to (card), or null when the
 * order was paid from the balance and is already active.
 */
class Checkout
{
    public function __construct(
        private PayFromBalance $balance,
        private StartCardPayment $card,
        private PaymentGatewayManager $gateways,
    ) {}

    /**
     * @throws InsufficientBalance
     * @throws PaymentGatewayError
     * @throws PurchaseNotAllowed
     */
    public function handle(Order $order, PaymentMethod $method, ?GatewayName $gateway = null): ?string
    {
        if ($method === PaymentMethod::Balance) {
            $this->balance->handle($order);

            return null;
        }

        $payment = $this->card->handle($order, $gateway ?? GatewayName::from($this->gateways->getDefaultDriver()));

        return (string) $payment->redirect_url;
    }
}
