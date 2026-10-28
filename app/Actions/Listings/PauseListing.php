<?php

namespace App\Actions\Listings;

use App\Contracts\Listing;
use App\Enums\ListingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The owner hides a published listing for a while; the expiry date keeps running.
 */
class PauseListing
{
    public function handle(User $owner, Model&Listing $listing): void
    {
        $listing->transitionTo(ListingStatus::Paused, by: $owner);
    }
}
