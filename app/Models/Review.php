<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Models\Concerns\Reportable;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rating 1-10 on a hotel, place or business profile. The reviewable's rating cache is refreshed
 * whenever a review is saved or removed.
 */
#[Guarded(['id', 'status', 'moderated_by', 'moderated_at'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, Reportable, SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending'];

    protected static function booted(): void
    {
        $refresh = function (Review $review): void {
            $reviewable = $review->reviewable()->withoutGlobalScopes()->first();

            if ($reviewable !== null && method_exists($reviewable, 'refreshRating')) {
                $reviewable->refreshRating();
            }
        };

        static::saved($refresh);
        static::deleted($refresh);
        static::restored($refresh);
    }

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'rating' => 'integer',
            'replied_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
