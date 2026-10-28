<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramBot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram Bot API over HTTPS (https://core.telegram.org/bots/api). Failures are logged and never
 * break the site: Telegram is a convenience channel.
 */
class HttpTelegramBot implements TelegramBot
{
    public function __construct(
        private string $token,
        private ?string $username = null,
    ) {}

    public function send(string $chatId, string $html, array $buttons = [], ?string $photoUrl = null): ?int
    {
        $payload = ['chat_id' => $chatId, 'parse_mode' => 'HTML'] + $this->keyboard($buttons);

        $result = $photoUrl !== null
            ? $this->call('sendPhoto', $payload + ['photo' => $photoUrl, 'caption' => mb_substr($html, 0, 1024)])
            : $this->call('sendMessage', $payload + ['text' => mb_substr($html, 0, 4096), 'link_preview_options' => ['is_disabled' => true]]);

        // A photo Telegram cannot fetch (local or protected URL): fall back to text.
        if ($result === null && $photoUrl !== null) {
            return $this->send($chatId, $html, $buttons);
        }

        return isset($result['message_id']) ? (int) $result['message_id'] : null;
    }

    public function edit(string $chatId, int $messageId, string $html, array $buttons = [], bool $caption = false): void
    {
        $payload = ['chat_id' => $chatId, 'message_id' => $messageId, 'parse_mode' => 'HTML'] + $this->keyboard($buttons, emptyRemoves: true);

        $caption
            ? $this->call('editMessageCaption', $payload + ['caption' => mb_substr($html, 0, 1024)])
            : $this->call('editMessageText', $payload + ['text' => mb_substr($html, 0, 4096)]);
    }

    public function answerCallback(string $callbackId, string $text = ''): void
    {
        $this->call('answerCallbackQuery', ['callback_query_id' => $callbackId, 'text' => mb_substr($text, 0, 200)]);
    }

    public function setWebhook(string $url, string $secret): bool
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ]) !== null;
    }

    public function username(): ?string
    {
        return $this->username;
    }

    /**
     * @param  list<list<array{0: string, 1: string}>>  $buttons
     * @return array<string, mixed>
     */
    private function keyboard(array $buttons, bool $emptyRemoves = false): array
    {
        if ($buttons === []) {
            return $emptyRemoves ? ['reply_markup' => ['inline_keyboard' => []]] : [];
        }

        return ['reply_markup' => ['inline_keyboard' => array_map(
            fn (array $row): array => array_map(fn (array $button): array => str_starts_with($button[1], 'https://') || str_starts_with($button[1], 'http://')
                ? ['text' => $button[0], 'url' => $button[1]]
                : ['text' => $button[0], 'callback_data' => $button[1]], $row),
            $buttons,
        )]];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function call(string $method, array $payload): ?array
    {
        try {
            $response = Http::asJson()->timeout(10)->post("https://api.telegram.org/bot{$this->token}/{$method}", $payload);
        } catch (ConnectionException $e) {
            Log::warning('Telegram is not reachable', ['method' => $method, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            Log::warning('Telegram refused a request', ['method' => $method, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);

            return null;
        }

        $result = $response->json('result');

        return is_array($result) ? $result : ['ok' => true];
    }
}
