<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * Life of an order: pending (waiting for the bank) -> paid -> fulfilled (the purchase is active).
 */
enum OrderStatus: string implements FilamentLabel, HasColor
{
    use HasLabel;

    case Pending = 'pending';
    case Paid = 'paid';
    case Fulfilled = 'fulfilled';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Money was received (fully or partly kept).
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Fulfilled, self::PartiallyRefunded], true);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Fulfilled, self::Paid => 'success',
            self::Pending => 'warning',
            self::Failed, self::Cancelled => 'gray',
            self::Refunded, self::PartiallyRefunded => 'danger',
        };
    }
}
