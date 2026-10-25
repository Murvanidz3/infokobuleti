<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Search\SearchableListing;
use App\Enums\AmenityScope;
use App\Enums\HotelType;
use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasDailyStats;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\IndexedForSearch;
use App\Models\Concerns\LogsStaffActivity;
use App\Models\Concerns\Promotable;
use App\Models\Concerns\Reportable;
use App\Models\Concerns\Reviewable;
use Database\Factories\HotelFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
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
class Hotel extends Model implements Listing, SearchableListing
{
    /** @use HasFactory<HotelFactory> */
    use Favoritable, HasDailyStats, HasFactory, HasLocation, HasMediaGallery, HasSlug, HasStatus, IndexedForSearch, HasTranslations, LogsStaffActivity, Promotable, Reportable, Reviewable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'address'];

    protected function casts(): array
    {
        return [
            'type' => HotelType::class,
            'price_from' => 'float',
        ];
    }

    public function isOpenInMonth(int $month): bool
    {
        if ($this->season_start_month === null || $this->season_end_month === null) {
            return true;
        }

        return $this->season_start_month <= $this->season_end_month
            ? $month >= $this->season_start_month && $month <= $this->season_end_month
            : $month >= $this->season_start_month || $month <= $this->season_end_month;
    }

    /**
     * Caches the lowest nightly base price across room types, shown as "from ₾…" on cards.
     */
    public static function syncPriceFrom(int $hotelId): void
    {
        static::withTrashed()->whereKey($hotelId)->toBase()->update([
            'price_from' => RoomType::query()->where('hotel_id', $hotelId)->min('base_price'),
        ]);
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
     * @return HasMany<RoomType, $this>
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class)->orderBy('sort');
    }

    /**
     * @return HasMany<BookingRequest, $this>
     */
    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    /**
     * @return MorphToMany<Amenity, $this>
     */
    public function amenities(): MorphToMany
    {
        return $this->morphToMany(Amenity::class, 'amenityable')->where('amenities.scope', AmenityScope::Hotel);
    }

    /**
     * @return array<string, mixed>
     */
    protected function searchableFilters(): array
    {
        return ['type' => $this->type->value, 'stars' => $this->stars, 'price' => $this->price_from, 'distance_to_sea_m' => $this->distance_to_sea_m, 'rating' => $this->rating_avg];
    }
}
