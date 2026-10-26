<?php

namespace App\Console\Commands;

use App\Services\Monitoring\Alerts;
use App\Services\Monitoring\HealthChecks;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('monitor:check')]
#[Description('Check disk, memory, queue worker, failed jobs, backups and SSL; alert on problems')]
class MonitorCheck extends Command
{
    public function handle(HealthChecks $health, Alerts $alerts): int
    {
        foreach ($health->run() as $check) {
            $label = __('monitoring.checks.'.$check['key']);
            $this->line(sprintf('%-4s %-28s %s', match ($check['ok']) {
                true => 'ok', false => 'FAIL', null => '—'
            }, $label, $check['value']));

            if ($check['ok'] === false) {
                $alerts->raise('check:'.$check['key'], __('monitoring.alert_title', ['check' => $label]), trim($check['value'].'. '.$check['detail'], '. '));
            } elseif ($check['ok'] === true) {
                $alerts->resolve('check:'.$check['key']);
            }
        }

        return self::SUCCESS;
    }
}
