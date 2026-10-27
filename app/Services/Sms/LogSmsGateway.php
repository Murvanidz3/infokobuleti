<?php

namespace App\Services\Sms;

use App\Contracts\SendsBulkSms;
use App\Contracts\SmsGateway;
use App\Contracts\SmsProviderAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes messages to the log instead of sending them (local and staging testing). It has no
 * balance and reports every message as delivered.
 */
class LogSmsGateway implements SendsBulkSms, SmsGateway, SmsProviderAccount
{
    public function __construct(private ?string $channel = null) {}

    public function send(string $to, string $text, SmsOptions $options = new SmsOptions): ?string
    {
        $id = 'log-'.Str::lower((string) Str::ulid());

        Log::channel($this->channel)->info("SMS to {$to}", ['id' => $id, 'text' => $text, 'otp' => $options->otp]);

        return $id;
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function sendBatch(array $messages, SmsOptions $options = new SmsOptions): ?string
    {
        $id = 'log-'.Str::lower((string) Str::ulid());

        foreach ($messages as $message) {
            Log::channel($this->channel)->info("SMS to {$message['phone']}", ['id' => $id, 'text' => $message['text']]);
        }

        return $id;
    }

    public function balance(): ?int
    {
        return null;
    }

    public function brands(): array
    {
        return [];
    }

    public function report(string $providerMessageId): array
    {
        return [];
    }
}
