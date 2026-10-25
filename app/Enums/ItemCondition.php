<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ItemCondition: string implements FilamentLabel
{
    use HasLabel;

    case New = 'new';
    case Used = 'used';
}
