<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Daily counters per listing (views, phone reveals, favorites, messages) for owner analytics.
 */
class ListingStats
{
    public const array METRICS = ['views', 'phone_reveals', 'favorites', 'messages'];

    public function record(Model $record, string $metric, int $by = 1): void
    {
        if (! in_array($metric, self::METRICS, true)) {
            return;
        }

        DB::table('daily_stats')->upsert(
            [[
                'statable_type' => $record->getMorphClass(),
                'statable_id' => $record->getKey(),
                'date' => today()->toDateString(),
                $metric => $by,
            ]],
            ['statable_type', 'statable_id', 'date'],
            [$metric => DB::raw("daily_stats.{$metric} + {$by}")],
        );
    }

    /**
     * Count a page view once per visitor session.
     */
    public function recordView(Model $record): void
    {
        $key = 'viewed.'.$record->getMorphClass().'.'.$record->getKey();

        if (session()->has($key)) {
            return;
        }

        session()->put($key, true);

        if (array_key_exists('views_count', $record->getAttributes())) {
            $record->newQuery()->whereKey($record->getKey())->increment('views_count');
        }

        $this->record($record, 'views');
    }
}
