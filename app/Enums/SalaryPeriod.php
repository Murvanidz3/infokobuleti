<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SalaryPeriod: string
{
    use HasLabel;

    case Hour = 'hour';
    case Day = 'day';
    case Month = 'month';
    case Season = 'season';
}
