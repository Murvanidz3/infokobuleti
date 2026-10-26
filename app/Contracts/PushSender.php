<?php

namespace App\Contracts;

use App\Models\PushSubscription;

/**
 * Web push delivery. Drivers: WebPushSender (VAPID keys, minishlink/web-push) and FakePushSender.
 */
interface PushSender
{
    /**
     * @param  array{title: string, body: string, url: string, tag?: string}  $payload
     * @return bool false when the subscription is gone (the caller deletes it)
     */
    public function send(PushSubscription $subscription, array $payload): bool;
}
