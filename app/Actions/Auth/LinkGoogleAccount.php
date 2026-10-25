<?php

namespace App\Actions\Auth;

use App\Exceptions\AuthFlowException;
use App\Models\User;
use Laravel\Socialite\Contracts\User as GoogleUser;

class LinkGoogleAccount
{
    /**
     * @throws AuthFlowException
     */
    public function handle(User $user, GoogleUser $google): void
    {
        if ($user->isStaff()) {
            throw new AuthFlowException('auth.staff_use_staff_login');
        }

        $takenBy = User::query()->where('google_id', $google->getId())->whereKeyNot($user->id)->exists();

        if ($takenBy) {
            throw new AuthFlowException('auth.google.taken');
        }

        $email = LoginWithGoogle::verifiedEmail($google);
        $emailIsFree = $email !== null && ! User::query()->where('email', $email)->whereKeyNot($user->id)->exists();

        $user->forceFill(array_filter([
            'google_id' => $google->getId(),
            'avatar' => $user->avatar ?? $google->getAvatar(),
            'email' => $user->email ?? ($emailIsFree ? $email : null),
            'email_verified_at' => $user->email_verified_at ?? ($emailIsFree ? now() : null),
        ], fn ($value) => $value !== null))->save();
    }
}
