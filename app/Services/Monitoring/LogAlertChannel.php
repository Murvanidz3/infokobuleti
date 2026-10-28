<?php

namespace App\Services\Monitoring;

use App\Contracts\AlertChannel;
use Illuminate\Support\Facades\Log;

/**
 * Always on: storage/logs/alerts.log.
 */
class LogAlertChannel implements AlertChannel
{
    public function enabled(): bool
    {
        return true;
    }

    public function send(string $title, string $text): void
    {
        Log::channel('alerts')->error($title.' — '.$text);
    }
}
