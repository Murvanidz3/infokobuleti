<?php

namespace App\Actions\Listings;

use App\Contracts\Listing;
use App\Enums\ListingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * One-click renewal: an approved listing gets a fresh expiry period from today and goes live again.
 * The content was already moderated, so it skips the moderation queue; edits go through it.
 */
class RenewListing
{
    public function handle(User $owner, Model&Listing $listing): void
    {
        $days = $listing->expiryDays();

        if ($days === null) {
            return;
        }

        DB::transaction(function () use ($owner, $listing, $days): void {
            $listing->setAttribute('expires_at', now()->addDays($days));

            if ($listing->listingStatus() === ListingStatus::Published) {
                $listing->save();

                activity('moderation')
                    ->performedOn($listing)
                    ->causedBy($owner)
                    ->event('renewed')
                    ->log('renewed');

                return;
            }

            // Expired or paused: publishing from these states keeps the new expiry date set above
            // (paused) or sets a fresh one (expired).
            $listing->transitionTo(ListingStatus::Published, by: $owner);
        });
    }
}
