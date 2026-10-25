<?php

namespace App\Actions\Auth;

use App\Exceptions\AuthFlowException;
use App\Models\User;

/**
 * Saves a new (already code-confirmed) phone number on an account.
 */
class ChangePhone
{
    /**
     * @throws AuthFlowException
     */
    public function handle(User $user, string $phone): void
    {
        if (User::query()->where('phone', $phone)->whereKeyNot($user->id)->exists()) {
            throw new AuthFlowException('auth.phone.taken');
        }

        $user->forceFill(['phone' => $phone, 'phone_verified_at' => now()])->save();
    }

    /**
     * @throws AuthFlowException when the phone is the only way left to sign in
     */
    public function remove(User $user): void
    {
        if (! $user->canRemoveSignInMethod('phone')) {
            throw new AuthFlowException('auth.methods.last_method');
        }

        $user->forceFill(['phone' => null, 'phone_verified_at' => null])->save();
    }
}
