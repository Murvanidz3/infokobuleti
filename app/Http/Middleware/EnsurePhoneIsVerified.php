<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "phone.verified": posting listings, sending messages and applying to jobs need a verified phone.
 */
class EnsurePhoneIsVerified
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedPhone()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('auth.phone.required')], 403);
            }

            return redirect()->guest(localized_route('account.phone'))->with('status', __('auth.phone.required'));
        }

        return $next($request);
    }
}
