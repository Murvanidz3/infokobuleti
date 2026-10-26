<?php

namespace App\Contracts;

use App\Enums\ListingStatus;
use App\Models\User;
use Spatie\MediaLibrary\HasMedia;

/**
 * Any moderated, publishable module item: hotel, property, classified, vacancy, place, article, event.
 *
 * Every implementation uses HasStatus and HasTranslations (spatie/laravel-translatable).
 *
 * @method $this setTranslations(string $key, array<string, mixed> $translations)
 */
interface Listing extends HasMedia
{
    /**
     * Columns only the system writes (moderation, counters, caches); never mass assignable.
     */
    public const array SYSTEM_COLUMNS = [
        'id', 'status', 'published_at', 'expires_at', 'rejection_reason', 'moderated_by', 'moderated_at',
        'risk_score', 'promotion_level', 'promoted_until', 'bumped_at', 'views_count', 'embedding',
        'price_gel', 'price_per_m2', 'price_from', 'rating_avg', 'reviews_count',
    ];

    public function transitionTo(ListingStatus $to, ?string $reason = null, ?User $by = null): static;

    public function isPublished(): bool;

    public function listingStatus(): ListingStatus;

    public function expiryDays(): ?int;
}
