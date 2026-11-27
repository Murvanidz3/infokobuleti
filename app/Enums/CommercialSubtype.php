<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum CommercialSubtype: string implements FilamentLabel
{
    use HasLabel;

    case Office = 'office';
    case Shop = 'shop';
    case Warehouse = 'warehouse';
    case HotelBusiness = 'hotel_business';
    case Other = 'other';
}
