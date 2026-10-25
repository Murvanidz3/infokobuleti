<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes messages to the log instead of sending them (local and staging testing).
 */
class LogSmsGateway implements SmsGateway
{
    public function __construct(private ?string $channel = null) {}

    public function send(string $to, string $text): ?string
    {
        $id = 'log-'.Str::lower((string) Str::ulid());

        Log::channel($this->channel)->info("SMS to {$to}", ['id' => $id, 'text' => $text]);

        return $id;
    }
}
