<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ApplyMethod: string
{
    use HasLabel;

    case Site = 'site';
    case Phone = 'phone';
    case Email = 'email';
}
