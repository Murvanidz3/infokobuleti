<?php

namespace App\Http\Middleware;

use App\Services\Auth\SessionTracker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs this browser out when it was removed from another device, and records its activity.
 */
class TrackUserSessions
{
    public function __construct(private SessionTracker $tracker) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $this->tracker->isRevoked($request)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => __('auth.session_ended')], 401)
                : redirect()->to(localized_route('login'))->with('status', __('auth.session_ended'));
        }

        $response = $next($request);

        if (($user = Auth::user()) !== null && $request->hasSession()) {
            $this->tracker->touch($request, $user);
        }

        return $response;
    }
}
