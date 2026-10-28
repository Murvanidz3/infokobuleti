<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Per-day series for the dashboard charts (last 30 days, missing days filled with zero).
 */
trait DailySeries
{
    protected const int DAYS = 30;

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    protected function series(Builder $query, string $dateColumn = 'created_at', string $aggregate = 'count(*)'): array
    {
        $from = today()->subDays(self::DAYS - 1);

        $rows = $query
            ->where($dateColumn, '>=', $from)
            ->selectRaw("date({$dateColumn}) as day, {$aggregate} as total")
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        foreach (range(self::DAYS - 1, 0) as $daysAgo) {
            $day = today()->subDays($daysAgo);
            $labels[] = $day->format('d.m');
            $values[] = (float) ($rows[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected static function since(): Carbon
    {
        return today()->subDays(self::DAYS - 1);
    }
}
