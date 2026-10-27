<?php

namespace App\Actions\Auth;

use App\Enums\AccountType;
use App\Enums\Role;
use App\Exceptions\AuthFlowException;
use App\Models\User;
use Laravel\Socialite\Contracts\User as GoogleUser;

/**
 * Signs in with Google in one step: an account with this Google id, else the account whose email
 * is verified and the same as Google's verified email (which gets linked), else a new account.
 *
 * An email typed on a phone account is unverified: anyone could type someone else's address, so it
 * never links a Google account. The verified Google owner wins the address instead (it is removed
 * from the phone account, which keeps signing in by phone).
 */
class LoginWithGoogle
{
    /**
     * @throws AuthFlowException
     */
    public function handle(GoogleUser $google): User
    {
        $user = User::query()->where('google_id', $google->getId())->first();
        $email = self::verifiedEmail($google);

        if ($user === null && $email !== null && ($match = User::query()->where('email', $email)->first()) !== null) {
            if ($match->email_verified_at !== null) {
                $user = $match;
            } elseif ($match->phone !== null && ! $match->isStaff()) {
                $match->forceFill(['email' => null, 'email_verified_at' => null])->save();
            } else {
                throw new AuthFlowException('auth.google.failed');
            }
        }

        if ($user === null) {
            // An unverified Google email is kept (unverified) only when no account uses it yet.
            $rawEmail = filled($google->getEmail()) ? mb_strtolower((string) $google->getEmail()) : null;
            $email ??= $rawEmail !== null && ! User::query()->where('email', $rawEmail)->exists() ? $rawEmail : null;

            if ($email === null) {
                throw new AuthFlowException('auth.google.failed');
            }

            $user = User::query()->create([
                'name' => $google->getName(),
                'email' => $email,
                'avatar' => $google->getAvatar(),
                'locale' => app()->getLocale(),
                'account_type' => AccountType::Individual,
            ]);
            $user->forceFill([
                'google_id' => $google->getId(),
                'email_verified_at' => self::verifiedEmail($google) !== null ? now() : null,
            ])->save();
            $user->assignRole(Role::User->value);

            return $user;
        }

        if ($user->is_blocked) {
            throw new AuthFlowException('auth.blocked');
        }

        // Staff sign in with password + authenticator code only, never with Google.
        if ($user->isStaff()) {
            throw new AuthFlowException('auth.staff_use_staff_login');
        }

        $user->forceFill(array_filter([
            'google_id' => $google->getId(),
            'avatar' => $user->avatar ?? $google->getAvatar(),
            'name' => $user->name ?? $google->getName(),
            'email_verified_at' => $user->email_verified_at ?? ($email !== null ? now() : null),
        ], fn ($value) => $value !== null))->save();

        return $user;
    }

    /**
     * The Google email, only when Google says it is verified.
     */
    public static function verifiedEmail(GoogleUser $google): ?string
    {
        $raw = method_exists($google, 'getRaw') ? $google->getRaw() : [];
        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        $email = $google->getEmail();

        return ($verified === true || $verified === 'true') && filled($email) ? mb_strtolower($email) : null;
    }
}
