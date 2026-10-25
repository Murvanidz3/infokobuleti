<?php

use App\Services\LocaleRouter;
use App\Services\Settings;

if (! function_exists('localized_route')) {
    /**
     * URL of a named localized route in the given (or current) locale.
     *
     * @param  array<string, mixed>  $parameters
     */
    function localized_route(string $name, array $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return app(LocaleRouter::class)->route($name, $parameters, $locale, $absolute);
    }
}

if (! function_exists('site_setting')) {
    /**
     * A site setting from the admin (Settings page), falling back to the given default.
     */
    function site_setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}
