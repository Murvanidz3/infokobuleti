<?php

namespace App\Listings;

final readonly class MediaRules
{
    public function __construct(
        public int $min,
        public int $max,
        public bool $required,
    ) {}
}
