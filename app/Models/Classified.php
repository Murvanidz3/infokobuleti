<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Enums\ItemCondition;
use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasDailyStats;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\IndexedForSearch;
use App\Models\Concerns\LogsStaffActivity;
use App\Models\Concerns\NormalizesPrice;
use App\Models\Concerns\Promotable;
use App\Models\Concerns\Reportable;
use App\Search\SearchableListing;
use Database\Factories\ClassifiedFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
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
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Classified extends Model implements Listing, SearchableListing
{
    /** @use HasFactory<ClassifiedFactory> */
    use Favoritable, HasDailyStats, HasFactory, HasLocation, HasMediaGallery, HasSlug, HasStatus, HasTranslations, IndexedForSearch, LogsStaffActivity, NormalizesPrice, Promotable, Reportable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'address'];

    protected function casts(): array
    {
        return [
            'condition' => ItemCondition::class,
            'negotiable' => 'boolean',
            'attribute_values' => 'array',
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
     * @return array<string, mixed>
     */
    protected function searchableFilters(): array
    {
        return ['category_id' => $this->category_id, 'price' => $this->price_gel];
    }
}
