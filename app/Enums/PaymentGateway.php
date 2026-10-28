<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PaymentGateway: string implements FilamentLabel
{
    use HasLabel;

    case Fake = 'fake';
    case Bog = 'bog';
    case Tbc = 'tbc';
}
