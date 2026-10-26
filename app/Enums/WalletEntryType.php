<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum WalletEntryType: string implements FilamentLabel
{
    use HasLabel;

    case TopUp = 'top_up';
    case Purchase = 'purchase';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
