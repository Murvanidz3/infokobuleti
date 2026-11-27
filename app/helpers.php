<?php

use App\Services\LocaleRouter;
use App\Services\Settings;
use Illuminate\Support\Arr;

if (! function_exists('localized_route')) {
    /**
     * URL of a named localized route in the given (or current) locale.
     *
     * @param  array<array-key, mixed>  $parameters  named values, or models in route-parameter order
     */
    function localized_route(string $name, array $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return app(LocaleRouter::class)->route($name, $parameters, $locale, $absolute);
    }
}

if (! function_exists('daily_rent_hotels_url')) {
    /**
     * The vacation listing (daily-rent stays). /hotels redirects here.
     */
    function daily_rent_hotels_url(?string $locale = null): string
    {
        return localized_route('real-estate.index', [
            'rubric' => 'vacation',
        ], $locale);
    }
}

if (! function_exists('viewing_vacation_index')) {
    /**
     * The real-estate index is showing vacation stays: the vacation rubric, or the old
     * daily-rent hotel URL.
     */
    function viewing_vacation_index(): bool
    {
        if (request('rubric') === 'vacation') {
            return true;
        }

        $types = array_values(array_filter(Arr::wrap(request('type')), fn (mixed $type): bool => is_string($type) && $type !== ''));

        return request('deal') === 'daily_rent' && $types !== [] && array_diff($types, ['hotel']) === [];
    }
}

if (! function_exists('nav_link')) {
    /**
     * Header and footer item: href, and whether it is the current section.
     *
     * A nav value is a route name, or [route name, parameters].
     *
     * @param  string|array{0: string, 1?: array<string, mixed>}  $route
     * @return array{href: string, current: bool}
     */
    function nav_link(string $key, string|array $route): array
    {
        [$name, $parameters] = is_array($route) ? [$route[0], $route[1] ?? []] : [$route, []];
        $patterns = [$name, '*.'.$name];

        if (str_ends_with($name, '.index')) {
            $wild = substr($name, 0, -strlen('.index')).'.*';
            $patterns[] = $wild;
            $patterns[] = '*.'.$wild;
        }

        $vacationIndex = request()->routeIs('real-estate.index', '*.real-estate.index') && viewing_vacation_index();

        $current = request()->routeIs(...$patterns);

        if ($key === 'hotels') {
            $current = $vacationIndex;
        } elseif ($key === 'real_estate' && $vacationIndex) {
            $current = false;
        }

        return [
            'href' => localized_route($name, $parameters),
            'current' => $current,
        ];
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
