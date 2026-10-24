<?php

use App\Services\LocaleRouter;

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
