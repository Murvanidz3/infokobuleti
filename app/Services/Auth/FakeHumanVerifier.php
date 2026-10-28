<?php

namespace App\Services\Auth;

use App\Contracts\HumanVerifier;

/**
 * Accepts every request except the token "fail" (so tests can exercise the rejection path).
 */
class FakeHumanVerifier implements HumanVerifier
{
    public function enabled(): bool
    {
        return false;
    }

    public function siteKey(): ?string
    {
        return null;
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        return $token !== 'fail';
    }
}
