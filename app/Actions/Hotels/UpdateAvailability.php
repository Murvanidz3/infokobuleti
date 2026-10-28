<?php

namespace App\Actions\Hotels;

use App\Models\RoomType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Closes, opens or re-prices a range of nights for one room type (the owner's calendar).
 * "Open" removes the exception rows, so the seasonal or base price applies again.
 */
class UpdateAvailability
{
    public const int MAX_DAYS = 366;

    /**
     * @param  'close'|'open'|'price'  $action
     */
    public function handle(RoomType $room, Carbon $from, Carbon $to, string $action, ?float $price = null): int
    {
        [$from, $to] = $from->lte($to) ? [$from->copy(), $to->copy()] : [$to->copy(), $from->copy()];

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            throw new InvalidArgumentException('Range too long.');
        }

        $dates = [];

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            if (! $day->isPast() || $day->isToday()) {
                $dates[] = $day->toDateString();
            }
        }

        if ($dates === []) {
            return 0;
        }

        DB::transaction(function () use ($room, $dates, $action, $price): void {
            if ($action === 'open') {
                // Keep price overrides, drop the closure; rows without anything left are removed.
                $room->availability()->whereIn('date', $dates)->update(['is_closed' => false, 'updated_at' => now()]);
                $room->availability()->whereIn('date', $dates)->whereNull('price_override')->whereNull('units_available')->delete();

                return;
            }

            $now = now();
            $rows = array_map(fn (string $date): array => [
                'room_type_id' => $room->id,
                'date' => $date,
                'is_closed' => $action === 'close',
                'price_override' => $action === 'price' ? $price : null,
                'units_available' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $dates);

            DB::table('room_availability')->upsert(
                $rows,
                ['room_type_id', 'date'],
                $action === 'close' ? ['is_closed', 'updated_at'] : ['price_override', 'is_closed', 'updated_at'],
            );
        });

        return count($dates);
    }
}
