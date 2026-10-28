<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * How an order is paid: by bank card (through a gateway) or from the wallet balance.
 */
enum PaymentMethod: string implements FilamentLabel
{
    use HasLabel;

    case Card = 'card';
    case Balance = 'balance';
}
