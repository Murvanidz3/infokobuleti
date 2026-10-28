<?php

namespace App\Services\Telegram;

use App\Contracts\TelegramBot;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

/**
 * Linking a site account to Telegram: the account page shows a one-time t.me link; opening it
 * sends "/start {token}" to the bot, which stores the user's private chat id.
 */
class TelegramLinks
{
    private const int TOKEN_TTL = 1800;

    public function __construct(
        private TelegramBot $bot,
        private Cache $cache,
    ) {}

    public function deepLink(User $user): ?string
    {
        $username = $this->bot->username();

        if (blank($username)) {
            return null;
        }

        $token = Str::random(32);
        $this->cache->put('telegram:link:'.$token, $user->getKey(), self::TOKEN_TTL);

        return 'https://t.me/'.$username.'?start='.$token;
    }

    /**
     * Links the chat to the account the token was made for. Returns that user, or null for an
     * unknown or expired token.
     */
    public function complete(string $token, string $chatId): ?User
    {
        $userId = $this->cache->pull('telegram:link:'.$token);
        $user = is_numeric($userId) ? User::query()->find((int) $userId) : null;

        if ($user === null) {
            return null;
        }

        // One Telegram chat belongs to one account.
        User::query()->where('telegram_chat_id', $chatId)->whereKeyNot($user->getKey())->update(['telegram_chat_id' => null]);
        $user->forceFill(['telegram_chat_id' => $chatId])->save();

        return $user;
    }

    public function unlinkChat(string $chatId): bool
    {
        return User::query()->where('telegram_chat_id', $chatId)->update(['telegram_chat_id' => null]) > 0;
    }
}
