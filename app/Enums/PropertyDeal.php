<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PropertyDeal: string
{
    use HasLabel;

    case Sale = 'sale';
    case Rent = 'rent';
    case DailyRent = 'daily_rent';
}
