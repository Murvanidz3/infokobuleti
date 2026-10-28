<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum Currency: string implements FilamentLabel
{
    use HasLabel;

    case GEL = 'GEL';
    case USD = 'USD';
    case EUR = 'EUR';

    public function symbol(): string
    {
        return match ($this) {
            self::GEL => '₾',
            self::USD => '$',
            self::EUR => '€',
        };
    }
}
