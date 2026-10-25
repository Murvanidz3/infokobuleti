<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PaymentGateway: string
{
    use HasLabel;

    case Fake = 'fake';
    case Bog = 'bog';
    case Tbc = 'tbc';
}
