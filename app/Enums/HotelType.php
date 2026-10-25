<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum HotelType: string
{
    use HasLabel;

    case Hotel = 'hotel';
    case Guesthouse = 'guesthouse';
    case Apartment = 'apartment';
    case Cottage = 'cottage';
    case Hostel = 'hostel';
}
