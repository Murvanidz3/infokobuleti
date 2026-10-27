<?php

namespace App\Services\Sms;

use App\Contracts\SendsBulkSms;
use App\Contracts\SmsGateway;
use App\Contracts\SmsProviderAccount;
use App\Exceptions\SmsDeliveryFailed;

/**
 * Keeps sent messages in memory. Used by the tests.
 */
class ArraySmsGateway implements SendsBulkSms, SmsGateway, SmsProviderAccount
{
    /** @var list<array{to: string, text: string, otp: bool, stop_list: bool, batch: string}> */
    public array $messages = [];

    public bool $failing = false;

    public ?int $balance = 1000;

    /** @var array<string, array<string, int>> provider id => number => status */
    public array $reports = [];

    public int $batches = 0;

    public function send(string $to, string $text, SmsOptions $options = new SmsOptions): ?string
    {
        if ($this->failing) {
            throw new SmsDeliveryFailed('The fake gateway is set to fail.');
        }

        $id = 'array-'.(count($this->messages) + 1);
        $this->messages[] = ['to' => $to, 'text' => $text, 'otp' => $options->otp, 'stop_list' => $options->respectStopList, 'batch' => $id];

        return $id;
    }

    public function batchSize(): int
    {
        return 2;
    }

    public function sendBatch(array $messages, SmsOptions $options = new SmsOptions): ?string
    {
        if ($this->failing) {
            throw new SmsDeliveryFailed('The fake gateway is set to fail.');
        }

        $id = 'batch-'.(++$this->batches);

        foreach ($messages as $message) {
            $this->messages[] = ['to' => $message['phone'], 'text' => $message['text'], 'otp' => $options->otp, 'stop_list' => $options->respectStopList, 'batch' => $id];
        }

        return $id;
    }

    /**
     * @return array{to: string, text: string, otp: bool, stop_list: bool, batch: string}|null
     */
    public function last(): ?array
    {
        return $this->messages === [] ? null : $this->messages[array_key_last($this->messages)];
    }

    public function balance(): ?int
    {
        return $this->balance;
    }

    public function brands(): array
    {
        return [['id' => 1, 'name' => (string) config('sms.sender'), 'authorized' => true, 'status' => 1]];
    }

    public function report(string $providerMessageId): array
    {
        return $this->reports[$providerMessageId] ?? [];
    }
}
