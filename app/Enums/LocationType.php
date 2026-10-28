<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum LocationType: string implements FilamentLabel
{
    use HasLabel;

    case Municipality = 'municipality';
    case Town = 'town';
    case Borough = 'borough';
    case Village = 'village';
    case District = 'district';
    case ProtectedArea = 'protected_area';
}
