<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BuildingStatus: string
{
    use HasLabel;

    case New = 'new';
    case Old = 'old';
    case UnderConstruction = 'under_construction';
}
