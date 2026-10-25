<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocked accounts are signed out on their next request.
 */
class LogoutBlockedUsers
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()?->is_blocked) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => __('auth.blocked')], 403)
                : redirect()->to(localized_route('login'))->with('error', __('auth.blocked'));
        }

        return $next($request);
    }
}
