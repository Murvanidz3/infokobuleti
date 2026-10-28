<?php

namespace App\Support\Listings;

use App\Models\Hotel;
use App\Models\RoomAvailability;
use App\Models\RoomType;
use App\Models\RoomTypePriceRule;
use App\Services\DateFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Month grids for a hotel page: per night, the lowest price among room types that can be booked.
 * Loads every rule and availability row of the period once (no query per day).
 */
class AvailabilityCalendar
{
    /**
     * @return list<array{label: string, offset: int, days: list<array{date: string, day: int, price: ?float, available: bool, past: bool}>}>
     */
    public function months(Hotel $hotel, int $count = 2, ?Carbon $from = null): array
    {
        $start = ($from ?? today())->copy()->startOfMonth();
        $end = $start->copy()->addMonthsNoOverflow($count - 1)->endOfMonth();

        /** @var Collection<int, RoomType> $rooms */
        $rooms = $hotel->roomTypes()->with([
            'priceRules' => fn ($query) => $query->where('ends_on', '>=', $start->toDateString())->where('starts_on', '<=', $end->toDateString())->orderByDesc('starts_on'),
            'availability' => fn ($query) => $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]),
        ])->get();

        $months = [];

        for ($month = $start->copy(); $month->lte($end); $month->addMonthNoOverflow()) {
            $days = [];

            for ($day = $month->copy(); $day->month === $month->month; $day->addDay()) {
                $days[] = $this->day($hotel, $rooms, $day);
            }

            $months[] = [
                'label' => app(DateFormatter::class)->monthYear($month),
                // Monday-first grid: empty cells before the 1st.
                'offset' => ($month->dayOfWeekIso - 1),
                'days' => $days,
            ];
        }

        return $months;
    }

    /**
     * @param  Collection<int, RoomType>  $rooms
     * @return array{date: string, day: int, price: ?float, available: bool, past: bool}
     */
    private function day(Hotel $hotel, Collection $rooms, Carbon $day): array
    {
        $past = $day->lt(today());
        $prices = [];

        if (! $past && $hotel->isOpenInMonth($day->month)) {
            foreach ($rooms as $room) {
                /** @var RoomAvailability|null $availability */
                $availability = $room->availability->first(fn (RoomAvailability $row): bool => $row->date->isSameDay($day));

                if ($availability !== null && ($availability->is_closed || $availability->units_available === 0)) {
                    continue;
                }

                if ($availability?->price_override !== null) {
                    $prices[] = (float) $availability->price_override;

                    continue;
                }

                /** @var RoomTypePriceRule|null $rule */
                $rule = $room->priceRules->first(fn (RoomTypePriceRule $rule): bool => $rule->starts_on->lte($day) && $rule->ends_on->gte($day));
                $prices[] = $rule !== null ? (float) $rule->price : (float) $room->base_price;
            }
        }

        return [
            'date' => $day->toDateString(),
            'day' => $day->day,
            'price' => $prices === [] ? null : min($prices),
            'available' => $prices !== [],
            'past' => $past,
        ];
    }
}
