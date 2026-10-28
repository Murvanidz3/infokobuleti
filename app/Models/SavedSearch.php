<?php

namespace App\Models;

use App\Enums\ListingModule;
use App\Enums\SearchFrequency;
use Database\Factories\SavedSearchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'module', 'name', 'filters', 'channels', 'frequency', 'is_active'])]
class SavedSearch extends Model
{
    /** @use HasFactory<SavedSearchFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'module' => ListingModule::class,
            'filters' => 'array',
            'channels' => 'array',
            'frequency' => SearchFrequency::class,
            'is_active' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    /**
     * The module's list page with the saved filters; with $edit, "save" there updates this search.
     */
    public function resultsUrl(bool $edit = false, ?string $locale = null): string
    {
        $route = match ($this->module) {
            ListingModule::Hotels => 'hotels.index',
            ListingModule::Properties => 'real-estate.index',
            ListingModule::Classifieds => 'classifieds.index',
            ListingModule::Vacancies => 'jobs.index',
        };

        $query = http_build_query(array_filter([...($this->filters ?? []), 'saved' => $edit ? $this->id : null], fn (mixed $value): bool => $value !== null));

        return localized_route($route, [], $locale).($query !== '' ? '?'.$query : '');
    }

    /**
     * Listings already reported to this search's owner.
     *
     * @return HasMany<SavedSearchHit, $this>
     */
    public function hits(): HasMany
    {
        return $this->hasMany(SavedSearchHit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
