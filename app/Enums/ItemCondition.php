<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ItemCondition: string
{
    use HasLabel;

    case New = 'new';
    case Used = 'used';
}
