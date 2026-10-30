<?php

namespace App\Services\Trust;

use App\Enums\SiteFeature;
use App\Models\Conversation;
use App\Models\Review;
use App\Models\User;
use App\Support\Features;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may review a place: someone who exchanged messages about it,
 * who is not its owner and has not reviewed it yet. With the trust layer switched off, any
 * signed-in non-owner may.
 */
class ReviewEligibility
{
    /**
     * null = may review; otherwise the reason key (account.reviews.blocked.*).
     */
    public function reason(User $user, Model $subject): ?string
    {
        if ((int) $subject->getAttribute('user_id') === (int) $user->getKey()) {
            return 'own';
        }

        if ($user->is_blocked) {
            return 'blocked';
        }

        if (Review::query()->withTrashed()->where('user_id', $user->getKey())->whereMorphedTo('reviewable', $subject)->exists()) {
            return 'already';
        }

        if (! Features::active(SiteFeature::TrustLayer)) {
            return null;
        }

        return $this->hasContact($user, $subject) ? null : 'no_contact';
    }

    public function canReview(User $user, Model $subject): bool
    {
        return $this->reason($user, $subject) === null;
    }

    private function hasContact(User $user, Model $subject): bool
    {
        return Conversation::query()
            ->whereMorphedTo('subject', $subject)
            ->whereHas('participants', fn ($query) => $query->whereKey($user->getKey()))
            ->whereHas('messages', fn ($query) => $query->where('user_id', '!=', $user->getKey()))
            ->exists();
    }
}
