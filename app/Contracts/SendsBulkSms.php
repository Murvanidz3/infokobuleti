<?php

namespace App\Contracts;

use App\Exceptions\SmsDeliveryFailed;
use App\Services\Sms\SmsOptions;

/**
 * Gateways that take many recipients in one request (one provider id for the whole batch).
 * Gateways without it get one send() per recipient.
 */
interface SendsBulkSms
{
    /** Recipients per request. */
    public function batchSize(): int;

    /**
     * @param  list<array{phone: string, text: string}>  $messages  E.164 numbers, unique
     * @return string|null the provider's id of the batch
     *
     * @throws SmsDeliveryFailed
     */
    public function sendBatch(array $messages, SmsOptions $options = new SmsOptions): ?string;
}
