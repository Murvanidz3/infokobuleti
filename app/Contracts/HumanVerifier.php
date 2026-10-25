<?php

namespace App\Contracts;

/**
 * Bot protection for public forms (Cloudflare Turnstile in production, a fake locally).
 */
interface HumanVerifier
{
    public function enabled(): bool;

    public function siteKey(): ?string;

    public function verify(?string $token, ?string $ip = null): bool;
}
