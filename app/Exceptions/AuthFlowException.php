<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A sign-in or account-linking step that cannot go ahead. The message is a lang key.
 */
class AuthFlowException extends RuntimeException
{
    public function __construct(public readonly string $key)
    {
        parent::__construct($key);
    }

    public function userMessage(): string
    {
        return __($this->key);
    }
}
