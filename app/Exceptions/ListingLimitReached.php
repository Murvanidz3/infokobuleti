<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The account used up its free listings for this month (admin setting per account type), or the
 * active listings its business package allows ($package = true).
 */
class ListingLimitReached extends RuntimeException
{
    public function __construct(public readonly int $limit, public readonly bool $package = false)
    {
        parent::__construct(($package ? 'Business package' : 'Monthly')." listing limit of {$limit} reached.");
    }
}
