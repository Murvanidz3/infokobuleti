<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ApplicationStatus: string implements FilamentLabel
{
    use HasLabel;

    case New = 'new';
    case Viewed = 'viewed';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
    case Hired = 'hired';
}
