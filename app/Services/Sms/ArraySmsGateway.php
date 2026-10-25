<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Exceptions\SmsDeliveryFailed;

/**
 * Keeps sent messages in memory. Used by the tests.
 */
class ArraySmsGateway implements SmsGateway
{
    /** @var list<array{to: string, text: string}> */
    public array $messages = [];

    public bool $failing = false;

    public function send(string $to, string $text): ?string
    {
        if ($this->failing) {
            throw new SmsDeliveryFailed('The fake gateway is set to fail.');
        }

        $this->messages[] = ['to' => $to, 'text' => $text];

        return 'array-'.count($this->messages);
    }

    /**
     * @return array{to: string, text: string}|null
     */
    public function last(): ?array
    {
        return $this->messages === [] ? null : $this->messages[array_key_last($this->messages)];
    }
}
