<?php

namespace App\Models\Concerns;

use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $location_id
 * @property float|null $lat
 * @property float|null $lng
 */
trait HasLocation
{
    private const int EARTH_RADIUS_M = 6371000;

    public function initializeHasLocation(): void
    {
        $this->mergeCasts(['lat' => 'float', 'lng' => 'float']);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Items inside a map viewport ("search in this area").
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithinBounds(Builder $query, float $south, float $west, float $north, float $east): void
    {
        $query->whereBetween($this->qualifyColumn('lat'), [$south, $north])
            ->whereBetween($this->qualifyColumn('lng'), [$west, $east]);
    }

    /**
     * Adds a distance_m column (haversine) and sorts by it, nearest first.
     *
     * @param  Builder<static>  $query
     */
    public function scopeNearest(Builder $query, float $lat, float $lng): void
    {
        $expression = sprintf(
            '%d * 2 * asin(sqrt(power(sin(radians(%s - ?) / 2), 2) + cos(radians(?)) * cos(radians(%s)) * power(sin(radians(%s - ?) / 2), 2)))',
            self::EARTH_RADIUS_M,
            $this->qualifyColumn('lat'),
            $this->qualifyColumn('lat'),
            $this->qualifyColumn('lng'),
        );

        if ($query->getQuery()->columns === null) {
            $query->select($this->qualifyColumn('*'));
        }

        $query->whereNotNull($this->qualifyColumn('lat'))
            ->selectRaw("{$expression} as distance_m", [$lat, $lat, $lng])
            ->orderBy('distance_m');
    }
}
