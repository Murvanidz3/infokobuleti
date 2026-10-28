<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PlaceType: string implements FilamentLabel
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
