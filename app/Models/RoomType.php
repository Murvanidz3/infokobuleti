<?php

namespace App\Models;

use App\Enums\AmenityScope;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\LogsStaffActivity;
use Carbon\CarbonInterface;
use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;

#[Fillable(['hotel_id', 'name', 'description', 'capacity_adults', 'capacity_children', 'beds', 'size_m2', 'base_price', 'units', 'sort'])]
class RoomType extends Model implements HasMedia
{
    /** @use HasFactory<RoomTypeFactory> */
    use HasFactory, HasMediaGallery, HasTranslations, LogsStaffActivity;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected static function booted(): void
    {
        static::saved(function (RoomType $roomType): void {
            if ($roomType->wasRecentlyCreated || $roomType->wasChanged(['base_price', 'hotel_id'])) {
                Hotel::syncPriceFrom($roomType->hotel_id);
            }
        });

        static::deleted(fn (RoomType $roomType) => Hotel::syncPriceFrom($roomType->hotel_id));
    }

    protected function casts(): array
    {
        return [
            'beds' => 'array',
            'base_price' => 'float',
        ];
    }

    /**
     * Nightly price for a date: availability override, then the matching seasonal rule, then the base price.
     */
    public function priceOn(CarbonInterface $date): float
    {
        $override = $this->availability()->whereDate('date', $date)->value('price_override');

        if ($override !== null) {
            return (float) $override;
        }

        $seasonal = $this->priceRules()
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->orderByDesc('starts_on')
            ->value('price');

        return $seasonal !== null ? (float) $seasonal : $this->base_price;
    }

    /**
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * @return HasMany<RoomTypePriceRule, $this>
     */
    public function priceRules(): HasMany
    {
        return $this->hasMany(RoomTypePriceRule::class);
    }

    /**
     * @return HasMany<RoomAvailability, $this>
     */
    public function availability(): HasMany
    {
        return $this->hasMany(RoomAvailability::class);
    }

    /**
     * @return MorphToMany<Amenity, $this>
     */
    public function amenities(): MorphToMany
    {
        return $this->morphToMany(Amenity::class, 'amenityable')->where('amenities.scope', AmenityScope::Room);
    }
}
