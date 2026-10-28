<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum BuildingStatus: string implements FilamentLabel
{
    use HasLabel;

    case New = 'new';
    case Old = 'old';
    case UnderConstruction = 'under_construction';
}
