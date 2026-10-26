<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Models\PushSubscription;

/**
 * Records pushes instead of sending them (tests, and servers without VAPID keys).
 */
class FakePushSender implements PushSender
{
    /** @var list<array{endpoint: string, payload: array<string, mixed>}> */
    public array $sent = [];

    /** @var list<string> endpoints that report "gone" */
    public array $gone = [];

    public function send(PushSubscription $subscription, array $payload): bool
    {
        if (in_array($subscription->endpoint, $this->gone, true)) {
            return false;
        }

        $this->sent[] = ['endpoint' => $subscription->endpoint, 'payload' => $payload];

        return true;
    }
}
