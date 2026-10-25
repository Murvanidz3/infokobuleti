<?php

namespace App\Models\Concerns;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Moderation lifecycle shared by every module. Allowed moves live in ListingStatus; this trait
 * applies the side effects (publish and expiry dates, moderation fields, activity log).
 *
 * Shared listing columns come from ListingSchema::columns(), which static analysis cannot read.
 *
 * @property int $user_id
 * @property int|null $business_profile_id
 * @property ListingStatus $status
 * @property Carbon|null $published_at
 * @property string|null $rejection_reason
 * @property int|null $moderated_by
 * @property Carbon|null $moderated_at
 */
trait HasStatus
{
    public function initializeHasStatus(): void
    {
        $this->mergeCasts([
            'status' => ListingStatus::class,
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
        ]);

        if ($this->expiryDays() !== null) {
            $this->mergeCasts(['expires_at' => 'datetime']);
        }

        $this->attributes['status'] ??= ListingStatus::Draft->value;
    }

    /**
     * @throws InvalidStatusTransition
     */
    public function transitionTo(ListingStatus $to, ?string $reason = null, ?User $by = null): static
    {
        $from = $this->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidStatusTransition::between($this, $from, $to);
        }

        if ($from === ListingStatus::Paused && $to === ListingStatus::Published && $this->isPastExpiry()) {
            throw InvalidStatusTransition::expired($this);
        }

        $this->status = $to;

        if ($to === ListingStatus::Published) {
            $this->published_at ??= now();
            $this->rejection_reason = null;

            if ($from !== ListingStatus::Paused && ($days = $this->expiryDays()) !== null) {
                $this->setAttribute('expires_at', now()->addDays($days));
            }
        }

        if ($to === ListingStatus::Rejected) {
            $this->rejection_reason = $reason;
        }

        if ($by !== null && in_array($to, [ListingStatus::Published, ListingStatus::Rejected], true)) {
            $this->moderated_by = $by->getKey();
            $this->moderated_at = now();
        }

        $this->save();

        activity('moderation')
            ->performedOn($this)
            ->causedBy($by)
            ->event('status_changed')
            ->withProperties(array_filter(['from' => $from->value, 'to' => $to->value, 'reason' => $reason]))
            ->log("status_changed: {$from->value} -> {$to->value}");

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->status === ListingStatus::Published;
    }

    /**
     * Days before a published item expires, or null when the module never expires.
     */
    public function expiryDays(): ?int
    {
        return app(Settings::class)->expiryDays($this->getMorphClass());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), ListingStatus::Published);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWithStatus(Builder $query, ListingStatus ...$statuses): void
    {
        $query->whereIn($this->qualifyColumn('status'), $statuses);
    }

    private function isPastExpiry(): bool
    {
        $expiresAt = $this->getAttribute('expires_at');

        return $expiresAt !== null && now()->greaterThan($expiresAt);
    }
}
