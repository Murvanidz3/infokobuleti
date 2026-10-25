<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SearchFrequency: string
{
    use HasLabel;

    case Instant = 'instant';
    case Daily = 'daily';
    case Weekly = 'weekly';
}
