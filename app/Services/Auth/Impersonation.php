<?php

namespace App\Services\Auth;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Lets staff see the site as a regular user (support cases). The staff session is restored with
 * leave(). Staff accounts and blocked users cannot be impersonated.
 */
class Impersonation
{
    public const string SESSION_KEY = 'auth.impersonator_id';

    public static function canImpersonate(User $staff, User $target): bool
    {
        return $staff->isStaff()
            && $staff->getKey() !== $target->getKey()
            && ! $target->isStaff()
            && ! $target->is_blocked
            && $staff->can(Permission::ManageUsers->value);
    }

    public function start(User $staff, User $target): void
    {
        abort_unless(self::canImpersonate($staff, $target), 403);

        session()->put(self::SESSION_KEY, $staff->getKey());
        Auth::guard('web')->login($target);

        activity('admin')->performedOn($target)->causedBy($staff)->event('impersonated')->log('impersonation started');
    }

    public function isActive(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function impersonator(): ?User
    {
        $id = session(self::SESSION_KEY);

        return $id === null ? null : User::query()->find($id);
    }

    public function leave(): ?User
    {
        $staff = $this->impersonator();
        session()->forget(self::SESSION_KEY);

        if ($staff === null) {
            Auth::guard('web')->logout();

            return null;
        }

        Auth::guard('web')->login($staff);

        return $staff;
    }
}
