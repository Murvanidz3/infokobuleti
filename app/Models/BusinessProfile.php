<?php

namespace App\Models;

use App\Enums\BusinessType;
use App\Models\Concerns\HasDailyStats;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Reportable;
use App\Models\Concerns\Reviewable;
use Database\Factories\BusinessProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

#[Guarded(['id', 'verified_at', 'rating_avg', 'reviews_count'])]
class BusinessProfile extends Model implements HasMedia
{
    /** @use HasFactory<BusinessProfileFactory> */
    use HasDailyStats, HasFactory, HasLocation, HasSlug, HasTranslations, InteractsWithMedia, Reportable, Reviewable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['description', 'address'];

    protected function casts(): array
    {
        return [
            'type' => BusinessType::class,
            'socials' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Hotel, $this>
     */
    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

    /**
     * @return HasMany<Property, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * @return HasMany<Vacancy, $this>
     */
    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    /**
     * @return HasMany<Place, $this>
     */
    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}
