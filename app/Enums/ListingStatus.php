<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ListingStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Paused = 'paused';
    case Expired = 'expired';
    case Archived = 'archived';

    /**
     * Who may perform a transition (owner, moderator, staff) is decided by policies, not here.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Pending, self::Published],
            self::Pending => [self::Published, self::Rejected, self::Draft],
            self::Rejected => [self::Pending],
            self::Published => [self::Paused, self::Expired, self::Archived, self::Pending],
            self::Paused => [self::Published, self::Archived],
            self::Expired => [self::Pending, self::Archived],
            self::Archived => [self::Draft],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
