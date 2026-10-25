<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AccountType: string
{
    use HasLabel;

    case Individual = 'individual';
    case Business = 'business';
}
