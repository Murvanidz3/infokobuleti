<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum SalaryPeriod: string implements FilamentLabel
{
    use HasLabel;

    case Hour = 'hour';
    case Day = 'day';
    case Month = 'month';
    case Season = 'season';
}
