<?php

namespace App\Actions\Listings;

use App\Contracts\Listing;
use App\Enums\ListingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A paused listing goes live again without another moderation round (its content is unchanged).
 */
class ResumeListing
{
    public function handle(User $owner, Model&Listing $listing): void
    {
        $listing->transitionTo(ListingStatus::Published, by: $owner);
    }
}
