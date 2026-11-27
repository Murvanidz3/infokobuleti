<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum VacationType: string implements FilamentLabel
{
    use HasLabel;

    case Hotel = 'hotel';
    case Guesthouse = 'guesthouse';
    case Apartment = 'apartment';
    case House = 'house';
    case Hostel = 'hostel';

    /**
     * Property type column that can hold this vacation kind without a new enum case.
     * Guesthouses and hostels stay `hotel` on that column; `vacation_type` keeps the real kind.
     */
    public function storedPropertyType(): PropertyType
    {
        return match ($this) {
            self::Apartment => PropertyType::Apartment,
            self::House => PropertyType::House,
            self::Hotel, self::Guesthouse, self::Hostel => PropertyType::Hotel,
        };
    }
}
