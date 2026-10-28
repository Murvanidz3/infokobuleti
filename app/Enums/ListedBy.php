<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ListedBy: string implements FilamentLabel
{
    use HasLabel;

    case Owner = 'owner';
    case Agency = 'agency';
}
