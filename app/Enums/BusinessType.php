<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum BusinessType: string implements FilamentLabel
{
    use HasLabel;

    case Hotel = 'hotel';
    case Agency = 'agency';
    case Employer = 'employer';
    case Service = 'service';
    case Restaurant = 'restaurant';
}
