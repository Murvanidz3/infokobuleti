<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum HotelType: string implements FilamentLabel
{
    use HasLabel;

    case Hotel = 'hotel';
    case Guesthouse = 'guesthouse';
    case Apartment = 'apartment';
    case Cottage = 'cottage';
    case Hostel = 'hostel';
}
