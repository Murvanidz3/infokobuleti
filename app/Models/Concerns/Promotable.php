<?php

namespace App\Models\Concerns;

use App\Enums\PromotionType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Keeps promotion_level / promoted_until / bumped_at in sync with the promotions table so list
 * pages can sort with one indexed ORDER BY.
 *
 * @property int $promotion_level
 * @property Carbon|null $promoted_until
 * @property Carbon|null $bumped_at
 */
trait Promotable
{
    public function initializePromotable(): void
    {
        $this->mergeCasts([
            'promotion_level' => 'integer',
            'promoted_until' => 'datetime',
            'bumped_at' => 'datetime',
        ]);
    }

    /**
     * @return MorphMany<Promotion, $this>
     */
    public function promotions(): MorphMany
    {
        return $this->morphMany(Promotion::class, 'promotable');
    }

    /**
     * Super VIP > VIP > last bump > published date.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOrderByPromotion(Builder $query): void
    {
        $query->orderByDesc($this->qualifyColumn('promotion_level'))
            ->orderByRaw($this->qualifyColumn('bumped_at').' DESC NULLS LAST')
            ->orderByDesc($this->qualifyColumn('published_at'));
    }

    public function refreshPromotionCache(): void
    {
        $active = $this->promotions()->active()->get();

        $ranked = $active->filter(fn (Promotion $promotion): bool => $promotion->type->level() > 0);
        $lastBump = $this->promotions()->where('type', PromotionType::Bump)->whereNull('cancelled_at')->max('starts_at');

        $this->forceFill([
            'promotion_level' => (int) $ranked->max(fn (Promotion $promotion): int => $promotion->type->level()),
            'promoted_until' => $ranked->max('ends_at'),
            'bumped_at' => $lastBump,
        ])->saveQuietly();
    }
}
