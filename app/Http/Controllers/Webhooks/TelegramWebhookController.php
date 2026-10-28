<?php

namespace App\Http\Controllers\Webhooks;

use App\Contracts\TelegramBot;
use App\Enums\SiteFeature;
use App\Http\Controllers\Controller;
use App\Services\Telegram\ModerationBot;
use App\Services\Telegram\TelegramLinks;
use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Updates from the Telegram bot: account linking (/start TOKEN, /stop), moderation buttons and
 * typed rejection reasons. The URL carries the webhook secret and Telegram repeats it in the
 * X-Telegram-Bot-Api-Secret-Token header; both must match.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, TelegramBot $bot, TelegramLinks $links, ModerationBot $moderation): Response
    {
        $expected = (string) config('services.telegram.webhook_secret');

        abort_if($expected === '' || ! hash_equals($expected, $secret) || ! hash_equals($expected, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        if (! Features::active(SiteFeature::TelegramBot)) {
            return response()->noContent();
        }

        $callback = $request->input('callback_query');

        if (is_array($callback)) {
            $moderation->callback(
                (string) ($callback['id'] ?? ''),
                (string) ($callback['message']['chat']['id'] ?? ''),
                (string) ($callback['from']['id'] ?? ''),
                (int) ($callback['message']['message_id'] ?? 0),
                (string) ($callback['data'] ?? ''),
            );

            return response()->noContent();
        }

        $message = $request->input('message');

        if (! is_array($message) || ! is_string($message['text'] ?? null)) {
            return response()->noContent();
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $fromId = (string) ($message['from']['id'] ?? '');
        $private = ($message['chat']['type'] ?? '') === 'private';
        $text = trim($message['text']);

        if (preg_match('/^\/start(?:@\w+)?\s+(\S+)$/', $text, $matches) === 1 && $private) {
            $user = $links->complete($matches[1], $chatId);
            $bot->send($chatId, e($user !== null ? __('telegram.link.done', ['name' => $user->name ?? ''], 'ka') : __('telegram.link.expired', [], 'ka')));
        } elseif (preg_match('/^\/stop(?:@\w+)?$/', $text) === 1 && $private) {
            $links->unlinkChat($chatId);
            $bot->send($chatId, e(__('telegram.link.stopped', [], 'ka')));
        } elseif (! $moderation->reasonMessage($chatId, $fromId, $text) && $private) {
            $bot->send($chatId, e(__('telegram.help', ['url' => localized_route('account.notifications', [], 'ka')], 'ka')));
        }

        return response()->noContent();
    }
}
