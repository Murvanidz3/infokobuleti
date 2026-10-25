<?php

namespace App\Support\Listings;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Short-lived cache for public listing blocks (homepage sections, counts). Every key carries a
 * version that any listing change bumps, so visitors never see a removed item for long.
 */
final class PublicCache
{
    private const string VERSION_KEY = 'public-cache.version';

    public const int TTL = 300;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, Closure $callback, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();

        return Cache::remember('public:'.self::version().":{$locale}:{$key}", self::TTL, $callback);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
