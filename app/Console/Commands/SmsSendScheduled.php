<?php

namespace App\Console\Commands;

use App\Services\Sms\Campaigns;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sms:send-scheduled')]
#[Description('Start SMS campaigns whose scheduled time has come')]
class SmsSendScheduled extends Command
{
    public function handle(Campaigns $campaigns): int
    {
        $started = $campaigns->startDue();

        if ($started > 0) {
            $this->info("Started {$started} campaign(s).");
        }

        return self::SUCCESS;
    }
}
