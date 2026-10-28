<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ReportStatus: string implements FilamentLabel, HasColor
{
    use HasLabel;

    case Open = 'open';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Resolved => 'success',
            self::Dismissed => 'gray',
        };
    }
}
