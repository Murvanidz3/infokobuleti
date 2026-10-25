<?php

namespace App\Services\Auth;

use App\Models\PhoneVerification;

final readonly class OtpSent
{
    public function __construct(
        public PhoneVerification $verification,
        public int $resendAfter,
        public ?string $revealedCode = null,
    ) {}
}
