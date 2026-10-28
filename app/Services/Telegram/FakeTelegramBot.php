<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramBot;

/**
 * Records what the bot would send (tests, and local work without a bot token).
 */
class FakeTelegramBot implements TelegramBot
{
    /** @var list<array{chat: string, html: string, buttons: list<list<array{0: string, 1: string}>>, photo: ?string, id: int}> */
    public array $sent = [];

    /** @var list<array{chat: string, id: int, html: string, buttons: list<list<array{0: string, 1: string}>>}> */
    public array $edited = [];

    /** @var list<array{id: string, text: string}> */
    public array $answers = [];

    /** @var list<array{url: string, secret: string}> */
    public array $webhooks = [];

    private int $nextId = 100;

    public function __construct(public ?string $botUsername = null) {}

    public function send(string $chatId, string $html, array $buttons = [], ?string $photoUrl = null): ?int
    {
        $this->sent[] = ['chat' => $chatId, 'html' => $html, 'buttons' => $buttons, 'photo' => $photoUrl, 'id' => ++$this->nextId];

        return $this->nextId;
    }

    public function edit(string $chatId, int $messageId, string $html, array $buttons = [], bool $caption = false): void
    {
        $this->edited[] = ['chat' => $chatId, 'id' => $messageId, 'html' => $html, 'buttons' => $buttons];
    }

    public function answerCallback(string $callbackId, string $text = ''): void
    {
        $this->answers[] = ['id' => $callbackId, 'text' => $text];
    }

    public function setWebhook(string $url, string $secret): bool
    {
        $this->webhooks[] = ['url' => $url, 'secret' => $secret];

        return true;
    }

    public function username(): ?string
    {
        return $this->botUsername;
    }

    /**
     * @return list<array{chat: string, html: string, buttons: list<list<array{0: string, 1: string}>>, photo: ?string, id: int}>
     */
    public function sentTo(string $chatId): array
    {
        return array_values(array_filter($this->sent, fn (array $message): bool => $message['chat'] === $chatId));
    }
}
