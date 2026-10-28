<?php

namespace App\Console\Commands;

use App\Services\Billing\Promotions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('promotions:run')]
#[Description('Run due auto-bumps, expire ended VIP placements and packages, and remind owners a day before')]
class RunPromotions extends Command
{
    public function handle(Promotions $promotions): int
    {
        $bumped = $promotions->runAutoBumps();
        $refreshed = $promotions->refreshExpired();
        $reminded = $promotions->notifyExpiring();

        $this->info("Auto-bumped {$bumped}, refreshed {$refreshed}, reminded {$reminded}.");

        return self::SUCCESS;
    }
}
