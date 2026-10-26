<?php

namespace App\Contracts;

use App\Enums\PaymentGateway as GatewayName;
use App\Exceptions\InvalidPaymentCallback;
use App\Exceptions\PaymentGatewayError;
use App\Models\Payment;
use App\Services\Payments\CallbackData;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\GatewayStatus;
use Illuminate\Http\Request;

/**
 * A bank's card-payment API. Drivers: fake (test bank), bog (Bank of Georgia), tbc (TBC Bank).
 *
 * The bank is always asked for the final status (fetchStatus) before an order is activated, so a
 * forged or replayed callback can never activate anything on its own.
 */
interface PaymentGateway
{
    public function name(): GatewayName;

    /**
     * Registers the payment with the bank and returns the page the payer is sent to.
     *
     * @throws PaymentGatewayError
     */
    public function createOrder(Payment $payment, string $description, string $returnUrl, string $callbackUrl, string $locale): CheckoutSession;

    /**
     * Checks a callback (signature, sender) and tells which bank order it is about.
     *
     * @throws InvalidPaymentCallback
     */
    public function verifyCallback(Request $request): CallbackData;

    /**
     * @throws PaymentGatewayError
     */
    public function fetchStatus(Payment $payment): GatewayStatus;

    /**
     * Returns money to the card: the full amount, or part of it.
     *
     * @return array<string, mixed> the bank's answer, for the payment log
     *
     * @throws PaymentGatewayError
     */
    public function refund(Payment $payment, float $amount): array;
}
