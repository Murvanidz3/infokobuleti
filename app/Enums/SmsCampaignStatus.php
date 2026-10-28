<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum SmsCampaignStatus: string implements FilamentLabel, HasColor
{
    use HasLabel;

    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'warning',
            self::Sending => 'info',
            self::Sent => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'gray',
        };
    }

    /** Recipients and text can still change. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Scheduled], true);
    }
}
