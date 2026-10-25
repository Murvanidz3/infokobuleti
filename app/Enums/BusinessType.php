<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BusinessType: string
{
    use HasLabel;

    case Hotel = 'hotel';
    case Agency = 'agency';
    case Employer = 'employer';
    case Service = 'service';
    case Restaurant = 'restaurant';
}
