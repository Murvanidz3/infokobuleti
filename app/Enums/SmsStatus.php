<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SmsStatus: string
{
    use HasLabel;

    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
