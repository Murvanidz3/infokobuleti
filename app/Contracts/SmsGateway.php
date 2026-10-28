<?php

namespace App\Contracts;

use App\Exceptions\SmsDeliveryFailed;
use App\Services\Sms\SmsOptions;

interface SmsGateway
{
    /**
     * Send a text message to an E.164 number.
     *
     * @return string|null the provider's message id, when it returns one
     *
     * @throws SmsDeliveryFailed
     */
    public function send(string $to, string $text, SmsOptions $options = new SmsOptions): ?string;
}
