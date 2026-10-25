<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Enums\AmenityScope;
use App\Enums\BuildingStatus;
use App\Enums\ListedBy;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasDailyStats;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\NormalizesPrice;
use App\Models\Concerns\Promotable;
use App\Models\Concerns\Reportable;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * price_per_m2 is a stored generated column (price / area_m2, in the listing's currency).
 *
 * @property float|null $price_per_m2
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Property extends Model implements Listing
{
    /** @use HasFactory<PropertyFactory> */
    use Favoritable, HasDailyStats, HasFactory, HasLocation, HasMediaGallery, HasSlug, HasStatus, HasTranslations, NormalizesPrice, Promotable, Reportable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'address'];

    protected function casts(): array
    {
        return [
            'deal' => PropertyDeal::class,
            'type' => PropertyType::class,
            'condition' => PropertyCondition::class,
            'building_status' => BuildingStatus::class,
            'listed_by' => ListedBy::class,
            'area_m2' => 'float',
            'land_area_m2' => 'float',
            'price_per_m2' => 'float',
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
     * @return MorphToMany<Amenity, $this>
     */
    public function features(): MorphToMany
    {
        return $this->morphToMany(Amenity::class, 'amenityable')->where('amenities.scope', AmenityScope::Property);
    }
}
