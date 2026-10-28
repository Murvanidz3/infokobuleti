<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ReviewStatus: string implements FilamentLabel, HasColor
{
    use HasLabel;

    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Published => 'success',
            self::Rejected => 'danger',
        };
    }
}
