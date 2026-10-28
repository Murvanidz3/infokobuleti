<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * Entries of the payment log: every request to and message from a bank.
 */
enum PaymentEventType: string implements FilamentLabel
{
    use HasLabel;

    case Created = 'created';
    case Callback = 'callback';
    case StatusCheck = 'status_check';
    case StatusChanged = 'status_changed';
    case Refund = 'refund';
    case Error = 'error';
}
