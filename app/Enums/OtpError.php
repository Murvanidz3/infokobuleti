<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Why a code could not be sent or accepted. Labels are the messages shown to the user.
 */
enum OtpError: string
{
    use HasLabel;

    case Cooldown = 'cooldown';
    case PhoneLimit = 'phone_limit';
    case IpLimit = 'ip_limit';
    case Expired = 'expired';
    case Invalid = 'invalid';
    case TooManyAttempts = 'too_many_attempts';

    public function isRateLimit(): bool
    {
        return in_array($this, [self::Cooldown, self::PhoneLimit, self::IpLimit], true);
    }
}
