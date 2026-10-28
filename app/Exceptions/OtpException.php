<?php

namespace App\Exceptions;

use App\Enums\OtpError;
use RuntimeException;

class OtpException extends RuntimeException
{
    public function __construct(
        public readonly OtpError $error,
        public readonly int $retryAfter = 0,
        public readonly ?int $remainingAttempts = null,
    ) {
        parent::__construct($error->value);
    }

    /**
     * Localized message for the user.
     */
    public function userMessage(): string
    {
        return match (true) {
            $this->error->isRateLimit() => __('enums.otp_error.'.$this->error->value, ['seconds' => $this->retryAfter, 'minutes' => (int) ceil($this->retryAfter / 60)]),
            $this->error === OtpError::Invalid => trans_choice('enums.otp_error.invalid', (int) $this->remainingAttempts, ['count' => (int) $this->remainingAttempts]),
            default => $this->error->label(),
        };
    }
}
