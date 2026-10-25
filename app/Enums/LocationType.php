<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum LocationType: string
{
    use HasLabel;

    case Municipality = 'municipality';
    case Town = 'town';
    case Borough = 'borough';
    case Village = 'village';
    case District = 'district';
    case ProtectedArea = 'protected_area';
}
