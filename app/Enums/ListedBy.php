<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ListedBy: string
{
    use HasLabel;

    case Owner = 'owner';
    case Agency = 'agency';
}
