<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-managed redirects (Admin → Redirects) for old addresses. Checked against a cached map, so
 * pages without a redirect cost one cache read. The query string is kept.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        try {
            $map = Redirect::map();
        } catch (QueryException) {
            return $next($request);
        }

        $match = $map[Redirect::normalize('/'.$request->path())] ?? null;

        if ($match === null) {
            return $next($request);
        }

        $target = $match['to'];

        if (Redirect::normalize($target) === Redirect::normalize('/'.$request->path()) && ! preg_match('#^https?://#i', $target)) {
            return $next($request);
        }

        if (! preg_match('#^https?://#i', $target)) {
            $target = url($target);
        }

        if ($request->getQueryString() !== null && ! str_contains($target, '?')) {
            $target .= '?'.$request->getQueryString();
        }

        Redirect::query()->whereKey($match['id'])->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);

        return redirect()->away($target, $match['code']);
    }
}
