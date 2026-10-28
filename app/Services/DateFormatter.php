<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Date strings used across listings. Patterns and relative phrases come from lang/{locale}/time.php
 * so each language controls its own wording (Georgian uses abbreviated units such as "2 h ago").
 */
class DateFormatter
{
    /**
     * "24 October, 2026".
     */
    public function date(CarbonInterface $date): string
    {
        return $date->locale(app()->getLocale())->isoFormat(__('time.formats.date'));
    }

    /**
     * "Saturday, 24 October". Weekday names come from lang files because Carbon's Georgian
     * names are in the dative case ("on Saturday"), while headings need the nominative ("Saturday").
     */
    public function weekdayDate(CarbonInterface $date): string
    {
        $date = $date->locale(app()->getLocale());

        return __('time.formats.weekday_date', [
            'weekday' => $this->weekday($date),
            'date' => $date->isoFormat(__('time.formats.day_month')),
        ]);
    }

    /**
     * Nominative weekday name, capitalised.
     */
    public function weekday(CarbonInterface $date): string
    {
        $key = 'time.weekdays.'.$date->dayOfWeek;

        return Str::ucfirst(Lang::has($key) ? __($key) : $date->locale(app()->getLocale())->dayName);
    }

    /**
     * "Oct" style month label for date badges, from lang/{locale}/time.php.
     */
    public function monthShort(CarbonInterface $date): string
    {
        return (string) __('time.months_short.'.$date->month);
    }

    /**
     * "October 2026" (nominative) for calendar headings.
     */
    public function monthYear(CarbonInterface $date): string
    {
        return __('time.months.'.$date->month).' '.$date->year;
    }

    public function time(CarbonInterface $date): string
    {
        return $date->format('H:i');
    }

    /**
     * Hours for anything under a day, then "yesterday" and "N days ago".
     */
    public function relative(CarbonInterface $moment, ?CarbonInterface $now = null): string
    {
        $now ??= now();
        $minutes = (int) floor($moment->diffInMinutes($now));

        if ($minutes < 60) {
            return trans_choice('time.minutes_ago', max($minutes, 1), ['count' => max($minutes, 1)]);
        }

        if ($minutes < 24 * 60) {
            $hours = intdiv($minutes, 60);

            return trans_choice('time.hours_ago', $hours, ['count' => $hours]);
        }

        return $this->relativeDay($moment, $now);
    }

    /**
     * Calendar-day precision: "today", "yesterday", "N days ago".
     */
    public function relativeDay(CarbonInterface $moment, ?CarbonInterface $now = null): string
    {
        $now ??= now();
        $days = (int) floor($moment->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()));

        return match (true) {
            $days <= 0 => __('time.today'),
            $days === 1 => __('time.yesterday'),
            default => trans_choice('time.days_ago', $days, ['count' => $days]),
        };
    }
}
