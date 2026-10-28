<?php

namespace App\Services\Insights;

use App\Contracts\ExchangeRates;
use App\Enums\Currency;
use App\Enums\ListingStatus;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\SiteFeature;
use App\Models\Location;
use App\Models\Property;
use App\Services\PriceFormatter;
use App\Support\Features;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Price per m² of a property against comparable live listings: same deal and type, in the same
 * area when there are enough of them, otherwise across Kobuleti. Prices are compared in GEL.
 */
class PriceInsight
{
    /** Fewer comparables than this and no verdict is given. */
    public const int MIN_COMPARABLES = 5;

    /** Within ± this many percent of the median counts as "market price". */
    public const int FAIR_BAND = 5;

    public function __construct(
        private ExchangeRates $rates,
        private PriceFormatter $price,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function forProperty(Property $property): ?array
    {
        if ($property->area_m2 === null || $property->price_gel === null) {
            return null;
        }

        return $this->compute($property->deal, $property->type, (float) $property->price_gel, (float) $property->area_m2, $property->location_id, $property->id);
    }

    /**
     * For the add-listing form, before anything is saved.
     *
     * @return array<string, mixed>|null
     */
    public function estimate(?string $deal, ?string $type, mixed $price, mixed $currency, mixed $area, mixed $locationId): ?array
    {
        $deal = PropertyDeal::tryFrom((string) $deal);
        $type = PropertyType::tryFrom((string) $type);

        if ($deal === null || $type === null || ! is_numeric($price) || ! is_numeric($area) || (float) $area <= 0 || (float) $price <= 0) {
            return null;
        }

        $gel = $this->rates->toGel((float) $price, Currency::tryFrom((string) $currency) ?? Currency::GEL);

        return $this->compute($deal, $type, $gel, (float) $area, is_numeric($locationId) ? (int) $locationId : null, null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function compute(PropertyDeal $deal, PropertyType $type, float $priceGel, float $area, ?int $locationId, ?int $exceptId): ?array
    {
        if (! Features::active(SiteFeature::PriceInsight) || $area <= 0) {
            return null;
        }

        $stats = $locationId !== null ? $this->stats($deal, $type, $locationId, $exceptId) : null;
        $local = $stats !== null && $stats['count'] >= self::MIN_COMPARABLES;

        if (! $local) {
            $stats = $this->stats($deal, $type, null, $exceptId);
        }

        if ($stats['count'] < self::MIN_COMPARABLES || $stats['median'] <= 0) {
            return null;
        }

        $value = $priceGel / $area;
        $diff = (int) round(($value - $stats['median']) / $stats['median'] * 100);
        $verdict = match (true) {
            $diff < -self::FAIR_BAND => 'below',
            $diff > self::FAIR_BAND => 'above',
            default => 'fair',
        };

        $low = min($stats['min'], $value);
        $high = max($stats['max'], $value);
        $position = fn (float $amount): float => $high > $low ? round(($amount - $low) / ($high - $low) * 100, 1) : 50.0;

        return [
            'value' => $this->price->format(round($value)),
            'median' => $this->price->format(round($stats['median'])),
            'diff' => abs($diff),
            'verdict' => $verdict,
            'count' => $stats['count'],
            'area' => $local ? (string) (Location::query()->find($locationId)->name ?? __('insights.price.city')) : __('insights.price.city'),
            'bar' => [
                'p25' => $position($stats['p25']),
                'p75' => $position($stats['p75']),
                'median' => $position($stats['median']),
                'value' => $position($value),
            ],
            'range' => [$this->price->format(round($stats['p25'])), $this->price->format(round($stats['p75']))],
        ];
    }

    /**
     * Median, quartiles and range of GEL per m² for live comparables (cached for an hour).
     *
     * @return array{count: int, median: float, p25: float, p75: float, min: float, max: float}
     */
    private function stats(PropertyDeal $deal, PropertyType $type, ?int $locationId, ?int $exceptId): array
    {
        $key = 'insights:price:'.implode(':', [$deal->value, $type->value, $locationId ?? 'all', $exceptId ?? 0]);

        return Cache::remember($key, 3600, function () use ($deal, $type, $locationId, $exceptId): array {
            $row = DB::table('properties')
                ->whereNull('deleted_at')
                ->where('status', ListingStatus::Published->value)
                ->where('deal', $deal->value)
                ->where('type', $type->value)
                ->where('area_m2', '>', 0)
                ->whereNotNull('price_gel')
                ->when($locationId !== null, fn ($query) => $query->where('location_id', $locationId))
                ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
                ->selectRaw('count(*) as count')
                ->selectRaw('percentile_cont(0.5) within group (order by price_gel / area_m2) as median')
                ->selectRaw('percentile_cont(0.25) within group (order by price_gel / area_m2) as p25')
                ->selectRaw('percentile_cont(0.75) within group (order by price_gel / area_m2) as p75')
                ->selectRaw('min(price_gel / area_m2) as min, max(price_gel / area_m2) as max')
                ->first();

            return [
                'count' => (int) ($row->count ?? 0),
                'median' => (float) ($row->median ?? 0),
                'p25' => (float) ($row->p25 ?? 0),
                'p75' => (float) ($row->p75 ?? 0),
                'min' => (float) ($row->min ?? 0),
                'max' => (float) ($row->max ?? 0),
            ];
        });
    }
}
