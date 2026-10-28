<?php

namespace App\Contracts;

use App\Exceptions\SmsDeliveryFailed;

/**
 * Account information of an SMS provider: balance, sender names and delivery reports.
 */
interface SmsProviderAccount
{
    /**
     * SMS left on the balance; null when the driver has no balance (log, array).
     *
     * @throws SmsDeliveryFailed
     */
    public function balance(): ?int;

    /**
     * @return list<array{id: int, name: string, authorized: bool, status: int}>
     *
     * @throws SmsDeliveryFailed
     */
    public function brands(): array;

    /**
     * Delivery status per recipient of a sent message or batch: number (last 9 digits) => one of
     * self::REPORT_* codes.
     *
     * @return array<string, int>
     *
     * @throws SmsDeliveryFailed
     */
    public function report(string $providerMessageId): array;

    public const int REPORT_SENT = 0;

    public const int REPORT_DELIVERED = 1;

    public const int REPORT_UNDELIVERED = 2;

    public const int REPORT_PENDING = 3;

    public const int REPORT_ERROR = 4;
}
