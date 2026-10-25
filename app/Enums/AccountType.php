<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum AccountType: string implements FilamentLabel
{
    use HasLabel;

    case Individual = 'individual';
    case Business = 'business';
}
