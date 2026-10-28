<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

/**
 * Guests are sent to the staff sign-in (password + authenticator code) instead of a Filament login page.
 */
class AuthenticateAdmin extends Authenticate
{
    protected function redirectTo($request): ?string
    {
        return localized_route('staff.login', locale: SetAdminLocale::current());
    }
}
