<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * One of the two users blocked the other, so messages cannot be sent.
 */
class MessagingBlocked extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Messaging between these users is blocked.');
    }
}
