<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PropertyCondition: string implements FilamentLabel
{
    use HasLabel;

    case NewlyRenovated = 'newly_renovated';
    case OldRenovation = 'old_renovation';
    case NeedsRenovation = 'needs_renovation';
    case WhiteFrame = 'white_frame';
    case BlackFrame = 'black_frame';
    case GreenFrame = 'green_frame';
}
