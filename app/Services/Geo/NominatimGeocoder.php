<?php

namespace App\Services\Geo;

use App\Contracts\Geocoder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * OpenStreetMap Nominatim. Its usage policy allows at most one request per second with an
 * identifying User-Agent, so answers are cached for a day and requests are throttled site-wide.
 */
class NominatimGeocoder implements Geocoder
{
    public function __construct(
        private string $url,
        private string $userAgent,
        /** @var array{0: float, 1: float, 2: float, 3: float} west, north, east, south */
        private array $viewbox,
    ) {}

    public function search(string $query, string $locale): array
    {
        $query = Str::squish($query);

        if (mb_strlen($query) < 3) {
            return [];
        }

        return Cache::remember('geocode:'.$locale.':'.md5(mb_strtolower($query)), now()->addDay(), function () use ($query, $locale): array {
            // Site-wide: one request per second. When busy, the visitor simply gets no suggestions.
            if (RateLimiter::tooManyAttempts('nominatim', 1)) {
                return [];
            }

            RateLimiter::hit('nominatim', 1);

            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => $this->userAgent])
                ->get($this->url, [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'countrycodes' => 'ge',
                    'viewbox' => implode(',', $this->viewbox),
                    'bounded' => 1,
                    'limit' => 6,
                    'accept-language' => $locale,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $places = $response->json();

            if (! is_array($places)) {
                return [];
            }

            return collect($places)
                ->filter(fn (mixed $place): bool => is_array($place) && isset($place['lat'], $place['lon'], $place['display_name']))
                ->map(fn (array $place): array => [
                    'label' => Str::limit(implode(', ', array_slice(explode(', ', (string) $place['display_name']), 0, 4)), 120),
                    'lat' => round((float) $place['lat'], 7),
                    'lng' => round((float) $place['lon'], 7),
                ])
                ->values()
                ->all();
        });
    }
}
