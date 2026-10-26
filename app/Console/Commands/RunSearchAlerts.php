<?php

namespace App\Console\Commands;

use App\Enums\SiteFeature;
use App\Services\Alerts\SavedSearchMatcher;
use App\Support\Features;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('alerts:run')]
#[Description('Match new listings to saved searches and send the due alerts (email, web push, Telegram)')]
class RunSearchAlerts extends Command
{
    public function handle(SavedSearchMatcher $matcher): int
    {
        if (! Features::active(SiteFeature::SearchAlerts)) {
            $this->info('Saved-search alerts are switched off.');

            return self::SUCCESS;
        }

        $result = $matcher->run();
        $this->info("Matched {$result['matched']} listings, sent {$result['sent']} alerts.");

        return self::SUCCESS;
    }
}
