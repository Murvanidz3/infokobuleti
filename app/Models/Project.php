<?php

namespace App\Models;

use App\Contracts\ExchangeRates;
use App\Contracts\Listing;
use App\Enums\Currency;
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
use App\Search\SearchableListing;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A new-construction project: name, developer, completion date
 * and a starting price per square metre. Contact is a phone number; there is no booking.
 *
 * @property string $title
 * @property string|null $description
 * @property string|null $address
 * @property float $price_per_m2
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Project extends Model implements Listing, SearchableListing
{
    /** @use HasFactory<ProjectFactory> */
    use Favoritable, HasDailyStats, HasFactory, HasLocation, HasMediaGallery, HasSlug, HasStatus, HasTranslations, IndexedForSearch, LogsStaffActivity, Promotable, Reportable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'address'];

    protected static function booted(): void
    {
        static::saving(function (Project $project): void {
            if ($project->isDirty(['price_per_m2', 'currency']) || $project->price_gel === null) {
                $project->price_gel = app(ExchangeRates::class)->toGel((float) $project->price_per_m2, $project->currency ?? Currency::GEL);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'completion_on' => 'date',
            'price_per_m2' => 'float',
            'price_gel' => 'float',
            'currency' => Currency::class,
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
     * @return array<string, mixed>
     */
    protected function searchableFilters(): array
    {
        return [
            'price' => $this->price_gel !== null ? (float) $this->price_gel : null,
            'developer' => $this->developer,
            'completion_on' => $this->completion_on?->getTimestamp(),
            'distance_to_sea_m' => $this->distance_to_sea_m !== null ? (int) $this->distance_to_sea_m : null,
            'location_id' => $this->location_id !== null ? (int) $this->location_id : null,
        ];
    }
}
