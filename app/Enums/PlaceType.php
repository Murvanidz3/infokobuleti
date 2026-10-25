<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PlaceType: string
{
    use HasLabel;

    case Beach = 'beach';
    case Sight = 'sight';
    case Nature = 'nature';
    case Restaurant = 'restaurant';
    case Cafe = 'cafe';
    case Museum = 'museum';
    case Service = 'service';
}
