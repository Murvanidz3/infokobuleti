<?php

namespace App\Actions\Auth;

use App\Exceptions\AuthFlowException;
use App\Models\User;

class UnlinkGoogleAccount
{
    /**
     * @throws AuthFlowException when Google is the only way left to sign in
     */
    public function handle(User $user): void
    {
        if (! $user->canRemoveSignInMethod('google')) {
            throw new AuthFlowException('auth.methods.last_method');
        }

        $user->forceFill(['google_id' => null])->save();
    }
}
