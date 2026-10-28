<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PaymentStatus: string implements FilamentLabel
{
    use HasLabel;

    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    /**
     * Status changes a bank report may cause; anything else (a late "failed" after "paid", a
     * repeated callback) is ignored, which keeps callback handling idempotent.
     */
    public function canBecome(self $next): bool
    {
        return match ($this) {
            self::Pending => $next !== self::Pending,
            self::Failed, self::Cancelled => $next === self::Paid,
            self::Paid => in_array($next, [self::Refunded, self::PartiallyRefunded], true),
            self::PartiallyRefunded => in_array($next, [self::Refunded, self::PartiallyRefunded], true),
            self::Refunded => false,
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
