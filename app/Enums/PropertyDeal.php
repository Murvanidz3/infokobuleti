<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PropertyDeal: string implements FilamentLabel
{
    use HasLabel;

    case Sale = 'sale';
    case Rent = 'rent';
    case Pledge = 'pledge';
    case DailyRent = 'daily_rent';
}
