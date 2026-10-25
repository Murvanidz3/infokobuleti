<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PropertyType: string
{
    use HasLabel;

    case Apartment = 'apartment';
    case House = 'house';
    case Land = 'land';
    case Commercial = 'commercial';
    case HotelBusiness = 'hotel_business';
}
