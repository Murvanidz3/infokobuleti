<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Enums\AmenityScope;
use App\Enums\BuildingStatus;
use App\Enums\CommercialSubtype;
use App\Enums\LandPurpose;
use App\Enums\ListedBy;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\VacationType;
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
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string $title
 * @property string|null $description
 * @property string|null $address
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Property extends Model implements Listing, SearchableListing
{
    /** @use HasFactory<PropertyFactory> */
    use Favoritable, HasDailyStats, HasFactory, HasLocation, HasMediaGallery, HasSlug, HasStatus, HasTranslations, IndexedForSearch, LogsStaffActivity, NormalizesPrice, Promotable, Reportable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'address'];

    protected function casts(): array
    {
        return [
            'deal' => PropertyDeal::class,
            'type' => PropertyType::class,
            'condition' => PropertyCondition::class,
            'building_status' => BuildingStatus::class,
            'owner_verified_at' => 'datetime',
            'listed_by' => ListedBy::class,
            'commercial_subtype' => CommercialSubtype::class,
            'vacation_type' => VacationType::class,
            'purpose' => LandPurpose::class,
            'utilities' => 'array',
            'area_m2' => 'float',
            'land_area_m2' => 'float',
            'price_per_m2' => 'float',
            'guests' => 'integer',
            'stars' => 'integer',
            'rooms_count' => 'integer',
        ];
    }

    /**
     * A daily-rent stay: an explicit vacation type, or a legacy hotel that predates that column.
     *
     * @param  Builder<Property>  $query
     */
    public function scopeVacation(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereNotNull('vacation_type')
                ->orWhere(fn (Builder $query) => $query
                    ->where('type', PropertyType::Hotel->value)
                    ->where('deal', PropertyDeal::DailyRent->value));
        });
    }

    /**
     * Sale and monthly rent. Legacy daily-rent hotels stay on the vacation listing.
     *
     * @param  Builder<Property>  $query
     */
    public function scopeRealEstate(Builder $query): void
    {
        $query->whereNull('vacation_type')
            ->where(function (Builder $query): void {
                $query->where('type', '!=', PropertyType::Hotel->value)
                    ->orWhere('deal', '!=', PropertyDeal::DailyRent->value);
            });
    }

    public function isVacation(): bool
    {
        return $this->vacation_type !== null
            || ($this->type === PropertyType::Hotel && $this->deal === PropertyDeal::DailyRent);
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
        return $this->morphToMany(Amenity::class, 'amenityable')->whereIn('amenities.scope', [AmenityScope::Property, AmenityScope::Hotel]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function searchableFilters(): array
    {
        return ['deal' => $this->deal->value, 'type' => $this->type->value, 'price' => $this->price_gel, 'area_m2' => $this->area_m2, 'rooms' => $this->rooms];
    }
}
