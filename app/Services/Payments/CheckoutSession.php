<?php

namespace App\Services\Payments;

/**
 * The bank's answer to a new order: its id and the payment page for the payer.
 */
final readonly class CheckoutSession
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $gatewayOrderId,
        public string $redirectUrl,
        public array $raw = [],
    ) {}
}
