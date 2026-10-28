<?php

namespace App\Filament\Support;

use App\Enums\Permission;

/**
 * Resources and pages declare the staff permission they need; super_admin passes every check.
 */
trait ChecksPermission
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can(static::requiredPermission()->value);
    }

    abstract public static function requiredPermission(): Permission;
}
