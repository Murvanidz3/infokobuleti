<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum LandUtility: string implements FilamentLabel
{
    use HasLabel;

    case Water = 'water';
    case Electricity = 'electricity';
    case Gas = 'gas';
    case RoadAccess = 'road_access';
}
