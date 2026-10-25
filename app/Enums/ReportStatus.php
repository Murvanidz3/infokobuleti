<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ReportStatus: string
{
    use HasLabel;

    case Open = 'open';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
