<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Currency: string
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
