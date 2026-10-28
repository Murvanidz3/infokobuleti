<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;

/**
 * A payment's state as the bank reports it.
 */
final readonly class GatewayStatus
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public PaymentStatus $status,
        public ?float $paidAmount = null,
        public float $refundedAmount = 0.0,
        public ?string $cardMask = null,
        public array $raw = [],
    ) {}
}
