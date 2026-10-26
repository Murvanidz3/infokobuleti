<?php

namespace App\Console\Commands;

use App\Contracts\TelegramBot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:webhook')]
#[Description('Register this site\'s webhook with the Telegram bot (needs TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET)')]
class SetTelegramWebhook extends Command
{
    public function handle(TelegramBot $bot): int
    {
        $secret = (string) config('services.telegram.webhook_secret');

        if (blank(config('services.telegram.bot_token')) || $secret === '') {
            $this->warn('TELEGRAM_BOT_TOKEN or TELEGRAM_WEBHOOK_SECRET is not set; nothing to do.');

            return self::SUCCESS;
        }

        $url = route('webhooks.telegram', ['secret' => $secret]);

        if (! $bot->setWebhook($url, $secret)) {
            $this->error('Telegram refused the webhook. Check the bot token.');

            return self::FAILURE;
        }

        $this->info('Webhook registered: '.preg_replace('#/webhooks/telegram/.+$#', '/webhooks/telegram/***', $url));

        return self::SUCCESS;
    }
}
