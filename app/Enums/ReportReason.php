<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ReportReason: string
{
    use HasLabel;

    case Spam = 'spam';
    case Fraud = 'fraud';
    case Duplicate = 'duplicate';
    case WrongCategory = 'wrong_category';
    case Offensive = 'offensive';
    case AlreadySold = 'already_sold';
    case Other = 'other';
}
