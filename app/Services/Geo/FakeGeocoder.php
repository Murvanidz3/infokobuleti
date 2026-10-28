<?php

namespace App\Services\Geo;

use App\Contracts\Geocoder;
use Illuminate\Support\Str;

/**
 * Local and test geocoder: a few fixed Kobuleti addresses, matched by substring.
 */
class FakeGeocoder implements Geocoder
{
    /** @var list<array{label: string, lat: float, lng: float}> */
    public const array PLACES = [
        ['label' => 'Rustaveli St 120, Kobuleti', 'lat' => 41.8193, 'lng' => 41.7769],
        ['label' => 'Agmashenebeli St 45, Kobuleti', 'lat' => 41.8135, 'lng' => 41.7790],
        ['label' => 'Chavchavadze St 3, Kobuleti', 'lat' => 41.8240, 'lng' => 41.7781],
        ['label' => 'Tsikhisdziri, Kobuleti Municipality', 'lat' => 41.7680, 'lng' => 41.7550],
    ];

    public function search(string $query, string $locale): array
    {
        $query = Str::lower(Str::squish($query));

        if (mb_strlen($query) < 3) {
            return [];
        }

        return array_values(array_filter(self::PLACES, fn (array $place): bool => str_contains(Str::lower($place['label']), $query)));
    }
}
