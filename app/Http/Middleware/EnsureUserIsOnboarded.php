<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "onboarded": new accounts first tell us their name and account type.
 */
class EnsureUserIsOnboarded
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isOnboarded() && ! $user->isStaff()) {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.onboarding.required')], 403)
                : redirect()->guest(localized_route('onboarding'));
        }

        return $next($request);
    }
}
