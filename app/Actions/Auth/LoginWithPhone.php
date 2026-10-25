<?php

namespace App\Actions\Auth;

use App\Enums\AccountType;
use App\Enums\Role;
use App\Exceptions\AuthFlowException;
use App\Models\User;

/**
 * Finds or creates the account for a phone number whose SMS code was just confirmed.
 */
class LoginWithPhone
{
    /**
     * @throws AuthFlowException
     */
    public function handle(string $phone): User
    {
        $user = User::query()->where('phone', $phone)->first();

        if ($user === null) {
            $user = User::query()->create([
                'phone' => $phone,
                'locale' => app()->getLocale(),
                'account_type' => AccountType::Individual,
            ]);
            $user->forceFill(['phone_verified_at' => now()])->save();
            $user->assignRole(Role::User->value);

            return $user;
        }

        if ($user->is_blocked) {
            throw new AuthFlowException('auth.blocked');
        }

        if ($user->isStaff()) {
            throw new AuthFlowException('auth.staff_use_staff_login');
        }

        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return $user;
    }
}
