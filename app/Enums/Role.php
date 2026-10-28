<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum Role: string implements FilamentLabel
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
