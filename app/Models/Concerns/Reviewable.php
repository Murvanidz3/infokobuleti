<?php

namespace App\Models\Concerns;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property float|null $rating_avg
 * @property int $reviews_count
 */
trait Reviewable
{
    public function initializeReviewable(): void
    {
        $this->mergeCasts(['rating_avg' => 'float']);
    }

    /**
     * @return MorphMany<Review, $this>
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Recalculates the cached rating from published reviews.
     */
    public function refreshRating(): void
    {
        $published = $this->reviews()->where('status', ReviewStatus::Published);
        $average = $published->avg('rating');

        $this->forceFill([
            'rating_avg' => $average === null ? null : round((float) $average, 1),
            'reviews_count' => $published->count(),
        ])->saveQuietly();
    }
}
