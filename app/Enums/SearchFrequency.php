<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum SearchFrequency: string implements FilamentLabel
{
    use HasLabel;

    case Instant = 'instant';
    case Daily = 'daily';
    case Weekly = 'weekly';
}
