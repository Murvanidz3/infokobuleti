<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Role: string
{
    use HasLabel;

    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case Business = 'business';
    case User = 'user';

    public function isStaff(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::Moderator], true);
    }
}
