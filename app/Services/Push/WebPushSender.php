<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Web push with VAPID (minishlink/web-push). Keys: php artisan webpush:keys.
 */
class WebPushSender implements PushSender
{
    private ?WebPush $client = null;

    /**
     * @param  array{subject: string, public_key: string, private_key: string}  $vapid
     */
    public function __construct(private array $vapid) {}

    public function send(PushSubscription $subscription, array $payload): bool
    {
        try {
            $report = $this->client()->sendOneNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding,
                ]),
                (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ['TTL' => 86400, 'urgency' => 'normal', 'topic' => substr((string) preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($payload['tag'] ?? '')), 0, 32) ?: null],
            );
        } catch (Throwable $e) {
            Log::warning('Web push failed', ['error' => $e->getMessage()]);

            return true;
        }

        return ! $report->isSubscriptionExpired();
    }

    private function client(): WebPush
    {
        return $this->client ??= new WebPush(['VAPID' => [
            'subject' => $this->vapid['subject'],
            'publicKey' => $this->vapid['public_key'],
            'privateKey' => $this->vapid['private_key'],
        ]]);
    }
}
