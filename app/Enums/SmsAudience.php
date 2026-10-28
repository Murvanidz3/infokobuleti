<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * Where the recipients of a campaign come from.
 */
enum SmsAudience: string implements FilamentLabel
{
    use HasLabel;

    case Manual = 'manual';
    case Csv = 'csv';
    case Users = 'users';
}
