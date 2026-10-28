<?php

namespace App\Contracts;

/**
 * Address search for the add-listing map (Nominatim / OpenStreetMap, a fake locally and in tests).
 */
interface Geocoder
{
    /**
     * Places matching the query inside Kobuleti municipality, best first.
     *
     * @return list<array{label: string, lat: float, lng: float}>
     */
    public function search(string $query, string $locale): array;
}
