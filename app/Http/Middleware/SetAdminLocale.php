<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin panel is Georgian by default; staff can switch it to English (kept in the session).
 */
class SetAdminLocale
{
    public const array LOCALES = ['ka', 'en'];

    private const string SESSION_KEY = 'admin.locale';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = self::current();

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    public static function current(): string
    {
        $locale = session(self::SESSION_KEY);

        return in_array($locale, self::LOCALES, true) ? $locale : 'ka';
    }

    public static function set(string $locale): void
    {
        if (in_array($locale, self::LOCALES, true)) {
            session([self::SESSION_KEY => $locale]);
        }
    }
}
