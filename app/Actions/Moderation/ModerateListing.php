<?php

namespace App\Actions\Moderation;

use App\Contracts\Listing;
use App\Enums\ListingStatus;
use App\Models\User;
use App\Notifications\ListingModerated;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Approve or reject a listing of any module and tell its owner.
 */
class ModerateListing
{
    public function approve(?Model $listing, User $moderator): void
    {
        $listing = self::listing($listing);

        $listing->transitionTo(ListingStatus::Published, by: $moderator);

        self::owner($listing)?->notify(new ListingModerated($listing, approved: true));
    }

    /**
     * @param  string|null  $template  a key of config('moderation.reasons')
     */
    public function reject(?Model $listing, User $moderator, ?string $template, ?string $note = null): void
    {
        $listing = self::listing($listing);
        $owner = self::owner($listing);
        $reason = self::reasonText($template, $note, $owner?->preferredLocale());

        $listing->transitionTo(ListingStatus::Rejected, $reason, $moderator);

        $owner?->notify(new ListingModerated($listing, approved: false, reason: $reason));
    }

    /**
     * The template in the owner's language followed by the moderator's own words.
     */
    public static function reasonText(?string $template, ?string $note, ?string $locale = null): string
    {
        $parts = [];

        if ($template !== null && in_array($template, config('moderation.reasons'), true)) {
            $parts[] = __('moderation.reasons.'.$template, locale: $locale);
        }

        if (filled($note)) {
            $parts[] = trim((string) $note);
        }

        return implode(' ', $parts);
    }

    /**
     * @return Model&Listing
     */
    public static function listing(?Model $listing): Model
    {
        if (! $listing instanceof Listing) {
            throw new InvalidArgumentException('Only listings can be moderated.');
        }

        return $listing;
    }

    public static function owner(Model $listing): ?User
    {
        $owner = $listing->getRelationValue('user');

        return $owner instanceof User ? $owner : null;
    }

    public static function status(Model $listing): ?ListingStatus
    {
        $status = $listing->getAttribute('status');

        return $status instanceof ListingStatus ? $status : null;
    }
}
