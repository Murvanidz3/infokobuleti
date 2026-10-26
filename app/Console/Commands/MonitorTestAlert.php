<?php

namespace App\Console\Commands;

use App\Services\Monitoring\Alerts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('monitor:test-alert')]
#[Description('Send a test alert to every alert channel (log file, Telegram when configured)')]
class MonitorTestAlert extends Command
{
    public function handle(Alerts $alerts): int
    {
        $alerts->raise('test', __('monitoring.test_title'), __('monitoring.test_text', ['time' => now()->format('Y-m-d H:i')]), force: true);
        $this->info('Test alert sent. See storage/logs/alerts-'.now()->format('Y-m-d').'.log'.(filled(config('monitoring.telegram_chat_id')) ? ' and the Telegram chat.' : '.'));

        return self::SUCCESS;
    }
}
