<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\IndexedForSearch;
use App\Models\Concerns\LogsStaffActivity;
use App\Models\Concerns\Reportable;
use App\Search\SearchableListing;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * Translatable attributes read in the current locale (spatie/laravel-translatable).
 *
 * @property string $title
 * @property string|null $description
 * @property string|null $address
 * @property string|null $venue
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Event extends Model implements Listing, SearchableListing
{
    /** @use HasFactory<EventFactory> */
    use Favoritable, HasFactory, HasLocation, HasMediaGallery, HasSlug, HasStatus, HasTranslations, IndexedForSearch, LogsStaffActivity, Reportable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'address', 'venue'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
            'is_free' => 'boolean',
            'price_from' => 'float',
        ];
    }

    /**
     * Events that have not finished yet, soonest first.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('ends_at', '>=', now())
            ->orWhere(fn (Builder $query) => $query->whereNull('ends_at')->where('starts_at', '>=', now()->startOfDay())))
            ->orderBy('starts_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BusinessProfile, $this>
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Place, $this>
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function searchableFilters(): array
    {
        return ['starts_at' => $this->starts_at->getTimestamp(), 'category_id' => $this->category_id];
    }
}
