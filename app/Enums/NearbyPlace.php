<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum NearbyPlace: string implements FilamentLabel
{
    use HasLabel;

    case School = 'school';
    case Kindergarten = 'kindergarten';
    case Pharmacy = 'pharmacy';
    case Supermarket = 'supermarket';
    case BusStop = 'bus_stop';
    case Park = 'park';
    case Boulevard = 'boulevard';
    case Beach = 'beach';
}
