<?php

namespace App\Contracts;

/**
 * The Telegram Bot API, reduced to what the site needs. Drivers: HttpTelegramBot (real bot,
 * TELEGRAM_BOT_TOKEN) and FakeTelegramBot (tests, or no token: messages are only logged).
 *
 * Buttons are rows of [text, callback data] pairs; data is at most 64 bytes.
 */
interface TelegramBot
{
    /**
     * @param  list<list<array{0: string, 1: string}>>  $buttons
     * @return int|null the message id, or null when Telegram refused it
     */
    public function send(string $chatId, string $html, array $buttons = [], ?string $photoUrl = null): ?int;

    /**
     * Replaces a message's text (or caption) and buttons.
     *
     * @param  list<list<array{0: string, 1: string}>>  $buttons
     */
    public function edit(string $chatId, int $messageId, string $html, array $buttons = [], bool $caption = false): void;

    public function answerCallback(string $callbackId, string $text = ''): void;

    /**
     * Registers the webhook URL with Telegram.
     */
    public function setWebhook(string $url, string $secret): bool;

    public function username(): ?string;
}
