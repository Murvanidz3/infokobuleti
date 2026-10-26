<?php

namespace App\Livewire\Account;

use App\Enums\SiteFeature;
use App\Models\User;
use App\Services\Telegram\TelegramLinks;
use App\Support\Features;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Where alerts can reach the user besides email: Telegram (linked through the bot) and web push
 * on this device (subscribed in the browser by resources/js/ui/push.js).
 */
class NotificationChannels extends Component
{
    public ?string $telegramLink = null;

    public function linkTelegram(TelegramLinks $links): void
    {
        $this->telegramLink = $links->deepLink($this->user());

        if ($this->telegramLink === null) {
            $this->dispatch('toast', message: __('alerts.telegram.unavailable'), type: 'error');
        }
    }

    public function unlinkTelegram(): void
    {
        $this->user()->forceFill(['telegram_chat_id' => null])->save();
        $this->telegramLink = null;
        $this->dispatch('toast', message: __('alerts.telegram.unlinked'), type: 'success');
    }

    public function render(): View
    {
        $user = $this->user();

        return view('livewire.account.notification-channels', [
            'telegram' => Features::active(SiteFeature::TelegramBot),
            'linked' => $user->hasTelegram(),
            'push' => Features::active(SiteFeature::WebPush) && filled(config('webpush.vapid.public_key')),
            'vapidKey' => (string) config('webpush.vapid.public_key'),
            'devices' => $user->pushSubscriptions()->count(),
        ]);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
