<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ApplicationStatus: string
{
    use HasLabel;

    case New = 'new';
    case Viewed = 'viewed';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
    case Hired = 'hired';
}
