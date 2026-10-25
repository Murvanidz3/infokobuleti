<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Enums\PlaceType;
use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasDailyStats;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\Reportable;
use App\Models\Concerns\Reviewable;
use Database\Factories\PlaceFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * City guide entry. opening_hours: {"mon": [["09:00", "18:00"]], ..., "sun": []}; a missing day is unknown.
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Place extends Model implements Listing
{
    /** @use HasFactory<PlaceFactory> */
    use Favoritable, HasDailyStats, HasFactory, HasLocation, HasMediaGallery, HasSlug, HasStatus, HasTranslations, Reportable, Reviewable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'address', 'content'];

    protected function casts(): array
    {
        return [
            'type' => PlaceType::class,
            'opening_hours' => 'array',
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
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
