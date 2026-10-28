<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum NotificationChannel: string implements FilamentLabel
{
    use HasLabel;

    case Email = 'email';
    case Push = 'push';
    case Telegram = 'telegram';
}
