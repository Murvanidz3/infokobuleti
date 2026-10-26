<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Site\OgImageController;
use App\Services\ListingStats;
use App\Support\Listings\PublicCache;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page cache for visitors who are not signed in, on the hot public pages (config seo.page_cache).
 *
 * The HTML is stored with placeholders instead of the visitor's CSRF token and the CSP nonce, which
 * are put back on every hit. The key carries PublicCache's version, so any listing or settings
 * change shows at once. Views of detail pages are still counted on a hit.
 */
class CachePublicPages
{
    private const string CSRF = '__IK_CSRF__';

    private const string NONCE = '__IK_NONCE__';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->cacheable($request)) {
            return $next($request);
        }

        $key = 'page:'.PublicCache::version().':'.$this->buildVersion().':'.md5($request->getSchemeAndHttpHost().'/'.$request->path().'?'.$this->query($request));
        $html = Cache::get($key);

        if (is_string($html)) {
            $this->countView($request);

            return new HttpResponse(strtr($html, [self::CSRF => csrf_token(), self::NONCE => (string) Vite::cspNonce()]), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Page-Cache' => 'HIT',
            ]);
        }

        $response = $next($request);

        if ($this->storable($response)) {
            $content = (string) $response->getContent();
            $replace = [csrf_token() => self::CSRF];

            if (filled($nonce = Vite::cspNonce())) {
                $replace[$nonce] = self::NONCE;
            }

            Cache::put($key, strtr($content, $replace), (int) config('seo.page_cache.ttl', 300));
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }

    /**
     * Changes with every front-end build, so cached pages never point to old CSS / JS files.
     */
    private function buildVersion(): int
    {
        $manifest = public_path('build/manifest.json');

        return is_file($manifest) ? (int) filemtime($manifest) : 0;
    }

    private function cacheable(Request $request): bool
    {
        if (! config('seo.page_cache.enabled') || ! $request->isMethodCacheable() || $request->ajax() || ! Auth::guest()) {
            return false;
        }

        if (strlen((string) $request->getQueryString()) > 300 || ! $request->hasSession()) {
            return false;
        }

        $session = $request->session();

        if ($session->get('_flash.old', []) !== [] || $session->get('_flash.new', []) !== [] || $session->has('errors') || $session->has('url.intended')) {
            return false;
        }

        $name = (string) $request->route()?->getName();
        $base = (string) preg_replace('/^[a-z]{2}\./', '', $name);

        return $name !== '' && in_array($base, (array) config('seo.page_cache.routes'), true);
    }

    private function storable(Response $response): bool
    {
        return $response->getStatusCode() === 200
            && $response instanceof HttpResponse
            && str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')
            && $response->headers->getCookies() === []
            && ! str_contains((string) $response->headers->get('Cache-Control'), 'no-store');
    }

    /**
     * The query string in a stable order, so "?a=1&b=2" and "?b=2&a=1" share one entry.
     */
    private function query(Request $request): string
    {
        $query = $request->query();
        ksort($query);

        return http_build_query($query);
    }

    /**
     * Detail pages count a view per visitor session even when they come from the cache.
     */
    private function countView(Request $request): void
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model && in_array($parameter::class, OgImageController::TYPES, true)) {
                app(ListingStats::class)->recordView($parameter);
            }
        }
    }
}
