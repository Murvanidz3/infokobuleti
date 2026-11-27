<?php

namespace App\Listings;

final readonly class LocationRules
{
    /**
     * @param  array{south: float, north: float, west: float, east: float}  $bounds
     */
    public function __construct(
        public bool $pinRequired,
        public bool $locationRequired,
        public array $bounds,
    ) {}
}
