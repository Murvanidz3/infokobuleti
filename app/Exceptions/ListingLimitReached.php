<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The account used up its free listings for this month (admin setting per account type).
 */
class ListingLimitReached extends RuntimeException
{
    public function __construct(public readonly int $limit)
    {
        parent::__construct("Monthly listing limit of {$limit} reached.");
    }
}
