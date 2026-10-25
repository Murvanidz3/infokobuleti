<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BookingStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
