<?php

namespace App\Support\Listings;

use App\Contracts\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may see a listing: everyone when it is published; its owner and staff otherwise (preview).
 */
final class Visibility
{
    public static function canView(Model $record): bool
    {
        return self::isLive($record) || self::canPreview($record);
    }

    /**
     * Published and not past its expiry date (same rule as ListingQuery::public()).
     */
    public static function isLive(Model $record): bool
    {
        if (! $record instanceof Listing) {
            return true;
        }

        $expiresAt = $record->getAttribute('expires_at');

        return $record->isPublished() && ($expiresAt === null || now()->lessThan($expiresAt));
    }

    /**
     * True for an unpublished listing that the current user may still look at.
     */
    public static function isPreview(Model $record): bool
    {
        return ! self::isLive($record) && self::canPreview($record);
    }

    /**
     * 404 unless visible; returns whether this is an owner/staff preview of an unpublished item.
     */
    public static function ensure(Model $record): bool
    {
        abort_unless(self::canView($record), 404);

        return self::isPreview($record);
    }

    private static function canPreview(Model $record): bool
    {
        if (! $record instanceof Listing) {
            return false;
        }

        $user = auth()->user();

        return $user instanceof User && ($user->isStaff() || (int) $record->getAttribute('user_id') === (int) $user->getKey());
    }
}
