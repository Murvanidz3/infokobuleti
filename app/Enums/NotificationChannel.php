<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum NotificationChannel: string
{
    use HasLabel;

    case Email = 'email';
    case Push = 'push';
    case Telegram = 'telegram';
}
