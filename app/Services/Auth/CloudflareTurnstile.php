<?php

namespace App\Services\Auth;

use App\Contracts\HumanVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @see https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
 */
class CloudflareTurnstile implements HumanVerifier
{
    public function __construct(private string $siteKey, private string $secretKey) {}

    public function enabled(): bool
    {
        return true;
    }

    public function siteKey(): ?string
    {
        return $this->siteKey;
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array_filter([
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (ConnectionException $exception) {
            // Do not lock everyone out while Cloudflare is unreachable; the SMS limits still apply.
            Log::warning('Turnstile is unreachable, request allowed.', ['error' => $exception->getMessage()]);

            return true;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
