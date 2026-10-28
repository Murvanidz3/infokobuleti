<?php

namespace App\Services\Monitoring;

use App\Contracts\AlertChannel;
use App\Contracts\TelegramBot;
use Throwable;

/**
 * Alerts to a Telegram chat or channel once the bot and MONITORING_TELEGRAM_CHAT_ID are set.
 */
class TelegramAlertChannel implements AlertChannel
{
    public function __construct(private TelegramBot $bot) {}

    public function enabled(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('monitoring.telegram_chat_id'));
    }

    public function send(string $title, string $text): void
    {
        try {
            $this->bot->send((string) config('monitoring.telegram_chat_id'), '<b>'.e($title).'</b>'."\n".e($text)."\n<i>".e((string) config('app.url')).'</i>');
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
