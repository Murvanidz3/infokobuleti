<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use Illuminate\Http\Response;

/**
 * robots.txt: production lets search engines in (except private areas) and names the sitemap;
 * every other environment (staging) is closed.
 */
class RobotsController extends Controller
{
    /** Private or endless areas, in every language prefix. */
    public const array PRIVATE_PATHS = ['/account', '/admin', '/staff', '/login', '/onboarding', '/payments', '/livewire', '/search', '/offline', '/auth'];

    public function __invoke(Settings $settings): Response
    {
        // Closed while the site is in maintenance (e.g. production before the launch).
        if (! app()->isProduction() || ! config('seo.indexable') || $settings->maintenanceMode()) {
            return $this->text("User-agent: *\nDisallow: /\n");
        }

        $lines = ['User-agent: *'];

        foreach (['', '/en', '/ru'] as $prefix) {
            foreach (self::PRIVATE_PATHS as $path) {
                $lines[] = 'Disallow: '.$prefix.$path;
            }
        }

        $lines[] = 'Disallow: /*?*sort=';
        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return $this->text(implode("\n", $lines)."\n");
    }

    private function text(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
