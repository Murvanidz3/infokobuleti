<?php

namespace App\Jobs;

use App\Contracts\Listing;
use App\Enums\ListingStatus;
use App\Services\Account\OwnerListings;
use App\Services\Telegram\ModerationBot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Posts a listing that entered the moderation queue to the staff's Telegram.
 */
class AnnounceToModerators implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $type,
        public int $id,
    ) {
        $this->afterCommit();
    }

    public function handle(ModerationBot $bot): void
    {
        $class = OwnerListings::TYPES[$this->type] ?? null;
        $listing = $class !== null ? $class::query()->find($this->id) : null;

        if ($listing instanceof Listing && $listing->listingStatus() === ListingStatus::Pending) {
            $bot->announce($listing);
        }
    }
}
