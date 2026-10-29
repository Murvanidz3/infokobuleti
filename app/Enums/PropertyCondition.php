<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PropertyCondition: string implements FilamentLabel
{
    use HasLabel;

    case NewlyRenovated = 'newly_renovated';
    case Renovated = 'renovated';
    case OldRenovation = 'old_renovation';
    case GreenFrame = 'green_frame';
    case WhiteFrame = 'white_frame';
    case BlackFrame = 'black_frame';
}
