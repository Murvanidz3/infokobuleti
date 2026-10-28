<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PropertyType: string implements FilamentLabel
{
    use HasLabel;

    case Apartment = 'apartment';
    case House = 'house';
    case Land = 'land';
    case Commercial = 'commercial';
    case HotelBusiness = 'hotel_business';
}
