<?php

namespace App\Console\Commands;

use App\Services\Sms\DeliveryReports;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * A fallback for delivery callbacks that never came: asks the provider for the reports of
 * recent messages that are still only "sent".
 */
#[Signature('sms:sync-reports {--limit=50 : Messages or batches per run}')]
#[Description('Fetch delivery reports of recent SMS from the provider')]
class SmsSyncReports extends Command
{
    public function handle(DeliveryReports $reports): int
    {
        $changed = $reports->sync(limit: max(1, (int) $this->option('limit')));
        $this->line("{$changed} message(s) updated.");

        return self::SUCCESS;
    }
}
