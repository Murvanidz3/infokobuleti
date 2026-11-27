<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum LandPurpose: string implements FilamentLabel
{
    use HasLabel;

    case Residential = 'residential';
    case Agricultural = 'agricultural';
    case Commercial = 'commercial';
}
