<?php

namespace App\Notifications\Channels;

use App\Contracts\TelegramBot;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Sends a notification's toTelegram() message to the user's linked Telegram chat.
 */
class TelegramChannel
{
    public function __construct(private TelegramBot $bot) {}

    public function send(User $notifiable, Notification $notification): void
    {
        if (! $notifiable->hasTelegram() || ! method_exists($notification, 'toTelegram')) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        $this->bot->send((string) $notifiable->telegram_chat_id, $message['html'], $message['buttons'] ?? []);
    }
}
