<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ReportReason: string implements FilamentLabel
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
