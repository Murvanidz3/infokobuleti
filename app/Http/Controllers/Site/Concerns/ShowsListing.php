<?php

namespace App\Http\Controllers\Site\Concerns;

use App\Models\User;
use App\Services\ListingStats;
use App\Support\Listings\Visibility;
use Illuminate\Database\Eloquent\Model;

trait ShowsListing
{
    /**
     * 404 unless the visitor may see the item; counts the view for everyone but the owner and staff.
     *
     * @return bool whether this is an owner/staff preview of an unpublished item
     */
    protected function visit(Model $record): bool
    {
        $preview = Visibility::ensure($record);
        $user = auth()->user();
        $isOwner = $user instanceof User && (int) $record->getAttribute('user_id') === $user->id;

        if (! $preview && ! $isOwner && ! ($user instanceof User && $user->isStaff())) {
            app(ListingStats::class)->recordView($record);
        }

        return $preview;
    }
}
