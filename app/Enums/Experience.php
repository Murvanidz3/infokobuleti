<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum Experience: string implements FilamentLabel
{
    use HasLabel;

    case None = 'none';
    case UnderOneYear = 'under_1';
    case OneToThreeYears = '1_3';
    case ThreeToFiveYears = '3_5';
    case OverFiveYears = 'over_5';
}
