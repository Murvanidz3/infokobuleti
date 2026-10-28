<?php

namespace App\Actions\Listings;

use App\Contracts\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The owner removes a listing. It is soft-deleted, so staff can still see it for abuse cases.
 */
class DeleteListing
{
    public function handle(User $owner, Model&Listing $listing): void
    {
        activity('moderation')
            ->performedOn($listing)
            ->causedBy($owner)
            ->event('deleted_by_owner')
            ->log('deleted_by_owner');

        $listing->delete();
    }
}
