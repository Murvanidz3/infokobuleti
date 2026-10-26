<?php

namespace App\Services\Payments;

/**
 * A verified callback: which bank order it concerns. $signatureValid is null for banks that do
 * not sign callbacks (TBC), where the status is always fetched from the API instead.
 */
final readonly class CallbackData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $gatewayOrderId,
        public array $payload,
        public ?bool $signatureValid,
    ) {}
}
