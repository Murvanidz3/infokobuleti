<?php

namespace App\Notifications\Channels;

use App\Contracts\PushSender;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Sends a notification's toWebPush() payload to every browser the user subscribed; expired
 * subscriptions are removed.
 */
class WebPushChannel
{
    public function __construct(private PushSender $sender) {}

    public function send(User $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);

        foreach ($notifiable->pushSubscriptions()->get() as $subscription) {
            if (! $this->sender->send($subscription, $payload)) {
                $subscription->delete();
            }
        }
    }
}
