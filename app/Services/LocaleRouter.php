<?php

namespace App\Services;

use App\Http\Middleware\SetLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

/**
 * Registers locale-prefixed route groups and builds URLs between them.
 *
 * The default locale owns the unprefixed URLs and plain route names ("home"); other locales get a
 * URL prefix and a route name prefix ("/en", "en.home").
 */
class LocaleRouter
{
    public function __construct(private Router $router) {}

    public function defaultLocale(): string
    {
        return config('locales.default');
    }

    /**
     * @return array<string, array{code: string, hreflang: string, og_locale: string, preload: list<string>}>
     */
    public function supported(): array
    {
        return config('locales.supported');
    }

    public function isSupported(string $locale): bool
    {
        return array_key_exists($locale, $this->supported());
    }

    /**
     * Register the given routes once per supported locale.
     *
     * @param  Closure(): void  $routes
     */
    public function group(Closure $routes): void
    {
        foreach (array_keys($this->supported()) as $locale) {
            $group = $this->router->middleware(SetLocale::class.':'.$locale);

            if ($locale !== $this->defaultLocale()) {
                $group->prefix($locale)->name($locale.'.');
            }

            $group->group($routes);
        }
    }

    public function routeName(string $name, string $locale): string
    {
        return $locale === $this->defaultLocale() ? $name : $locale.'.'.$name;
    }

    /**
     * Strip the locale prefix from a localized route name ("en.home" becomes "home").
     */
    public function baseName(string $routeName): string
    {
        foreach (array_keys($this->supported()) as $locale) {
            if ($locale !== $this->defaultLocale() && str_starts_with($routeName, $locale.'.')) {
                return substr($routeName, strlen($locale) + 1);
            }
        }

        return $routeName;
    }

    /**
     * URL of a named localized route in the given (or current) locale.
     *
     * @param  array<array-key, mixed>  $parameters  named values, or models in route-parameter order
     */
    public function route(string $name, array $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        $locale ??= app()->getLocale();

        return route($this->routeName($name, $locale), $parameters, $absolute);
    }

    /**
     * URL of the current page in another locale, keeping route parameters and the query string.
     * Pages without a localized counterpart fall back to that locale's homepage.
     */
    public function switchUrl(string $locale, bool $withQuery = true): string
    {
        $request = app(Request::class);
        $current = $request->route();
        $name = is_object($current) ? $current->getName() : null;

        if ($name !== null) {
            $target = $this->routeName($this->baseName($name), $locale);

            if ($this->router->has($target)) {
                $url = route($target, $current->parameters());
                $query = $withQuery ? $request->getQueryString() : null;

                return $query ? $url.'?'.$query : $url;
            }
        }

        return $this->route('home', locale: $locale);
    }

    /**
     * Alternate URLs of the current page for hreflang links, keyed by hreflang code. Query strings
     * (tracking parameters, filters) are dropped so search engines see one URL per language.
     *
     * @return array<string, string>
     */
    public function alternates(): array
    {
        $alternates = [];

        foreach ($this->supported() as $locale => $meta) {
            $alternates[$meta['hreflang']] = $this->switchUrl($locale, withQuery: false);
        }

        $alternates['x-default'] = $this->switchUrl($this->defaultLocale(), withQuery: false);

        return $alternates;
    }
}
