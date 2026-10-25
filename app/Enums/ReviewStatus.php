<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ReviewStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
}
