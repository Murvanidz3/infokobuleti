<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "staff": staff roles with two-factor authentication turned on (the admin panel uses this).
 */
class EnsureUserIsStaff
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(localized_route('staff.login'));
        }

        abort_unless($user->isStaff() && $user->hasTwoFactorEnabled(), 403);

        return $next($request);
    }
}
