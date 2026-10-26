<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers for the public site: Content-Security-Policy with a per-request nonce
 * for inline scripts, HSTS on HTTPS, frame, referrer, MIME and permissions policies.
 *
 * 'unsafe-eval' stays in script-src because Alpine.js evaluates its inline expressions; scripts
 * themselves only load from this site, Cloudflare Turnstile and Google Analytics.
 */
class SecurityHeaders
{
    /**
     * @param  string|null  $area  "admin": the Filament panel gets every header but the CSP (its
     *                             inline scripts carry no nonce).
     */
    public function handle(Request $request, Closure $next, ?string $area = null): Response
    {
        $nonce = $area === 'admin' ? '' : Vite::useCspNonce();

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), payment=(), usb=(), interest-cohort=(), geolocation=(self)');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($area !== 'admin' && ! $headers->has('Content-Security-Policy') && ! Vite::isRunningHot()) {
            $headers->set('Content-Security-Policy', $this->policy($nonce, $request->isSecure()));
        }

        return $response;
    }

    public function policy(string $nonce, bool $secure): string
    {
        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", "'unsafe-eval'", 'https://challenges.cloudflare.com', 'https://www.googletagmanager.com'],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'blob:', 'https://tile.openstreetmap.org', 'https://*.tile.openstreetmap.org', 'https://lh3.googleusercontent.com', 'https://www.googletagmanager.com', 'https://*.google-analytics.com'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'", 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://www.googletagmanager.com', 'https://challenges.cloudflare.com'],
            'frame-src' => ['https://challenges.cloudflare.com'],
            'worker-src' => ["'self'"],
            'manifest-src' => ["'self'"],
            'media-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'", 'https:'],
            'frame-ancestors' => ["'self'"],
        ];

        $policy = collect($directives)->map(fn (array $sources, string $name): string => $name.' '.implode(' ', $sources))->implode('; ');

        return $secure ? $policy.'; upgrade-insecure-requests' : $policy;
    }
}
