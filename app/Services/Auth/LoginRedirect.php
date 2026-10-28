<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Where to go after signing in: onboarding for new accounts, otherwise the page the user came from.
 */
class LoginRedirect
{
    public function for(User $user, ?string $returnTo = null): string
    {
        $target = self::isSafe($returnTo) ? $returnTo : null;
        $intended = session()->pull('url.intended');
        $target ??= self::isSafe($intended) ? $intended : null;
        $target ??= localized_route('home');

        if (! $user->isOnboarded() && ! $user->isStaff()) {
            session()->put('url.intended', $target);

            return localized_route('onboarding');
        }

        return $target;
    }

    /**
     * Only same-site URLs are followed, and never back to a sign-in page.
     */
    public static function isSafe(mixed $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $root = rtrim(url('/'), '/');

        if ($url !== $root && ! Str::startsWith($url, [$root.'/', $root.'?'])) {
            return false;
        }

        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return preg_match('#^(/(en|ru))?/(login|staff/|auth/)#', $path) !== 1;
    }
}
