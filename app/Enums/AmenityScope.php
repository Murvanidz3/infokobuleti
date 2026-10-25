<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AmenityScope: string
{
    use HasLabel;

    case Hotel = 'hotel';
    case Room = 'room';
    case Property = 'property';
}
