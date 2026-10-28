<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Notifications\ListingExpiry;
use App\Services\Account\OwnerListings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

#[Signature('listings:expire')]
#[Description('Mark listings past their expiry date as expired and remind owners 3 days before')]
class ExpireListings extends Command
{
    public function handle(): int
    {
        $expired = 0;
        $reminded = 0;
        $remindOn = today()->addDays(OwnerListings::EXPIRY_WARNING_DAYS);

        foreach (OwnerListings::TYPES as $class) {
            $class::query()
                ->with('user')
                ->where('status', ListingStatus::Published->value)
                ->where('expires_at', '<=', now())
                ->chunkById(200, function ($listings) use (&$expired): void {
                    foreach ($listings as $listing) {
                        $listing->transitionTo(ListingStatus::Expired);
                        $listing->getRelationValue('user')?->notify(new ListingExpiry($listing, expired: true, expiresAt: $listing->getAttribute('expires_at')));
                        $expired++;
                    }
                });

            // Runs once a day: everything expiring on that calendar day gets exactly one reminder.
            $class::query()
                ->with('user')
                ->whereIn('status', [ListingStatus::Published->value, ListingStatus::Paused->value])
                ->whereBetween('expires_at', [$remindOn->copy()->startOfDay(), $remindOn->copy()->endOfDay()])
                ->chunkById(200, function ($listings) use (&$reminded): void {
                    /** @var Model $listing */
                    foreach ($listings as $listing) {
                        $listing->getRelationValue('user')?->notify(new ListingExpiry($listing, expired: false, expiresAt: $listing->getAttribute('expires_at')));
                        $reminded++;
                    }
                });
        }

        $this->info("Expired: {$expired}. Reminders: {$reminded}.");

        return self::SUCCESS;
    }
}
