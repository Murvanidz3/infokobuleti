<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ListingStatus: string implements FilamentLabel, HasColor
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
            self::Paused => [self::Published, self::Pending, self::Archived],
            self::Expired => [self::Pending, self::Published, self::Archived],
            // Pending: the owner edits an archived listing and sends it back to moderation.
            self::Archived => [self::Draft, self::Pending],
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

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Published => 'success',
            self::Rejected => 'danger',
            self::Paused => 'gray',
            self::Expired => 'gray',
            self::Archived => 'gray',
        };
    }
}
