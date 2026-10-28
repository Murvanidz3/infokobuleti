<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum SmsStatus: string implements FilamentLabel, HasColor
{
    use HasLabel;

    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Sent => 'info',
            self::Delivered => 'success',
            self::Failed => 'danger',
        };
    }
}
