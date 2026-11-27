<?php

namespace App\Listings;

/**
 * How this listing kind prices itself. perSquareMeter is the stored generated column
 * (price / area_m2), not a separate input.
 */
final readonly class PriceModel
{
    /**
     * @param  list<string>  $currencies
     */
    public function __construct(
        public PriceKind $kind,
        public bool $required,
        public int $min,
        public int $max,
        public array $currencies,
        public bool $perSquareMeter,
    ) {}
}
