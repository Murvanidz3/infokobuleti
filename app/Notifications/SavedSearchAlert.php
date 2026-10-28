<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationTopic;
use App\Enums\SiteFeature;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WebPushChannel;
use App\Support\Features;
use App\Support\Listings\PublicUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * New listings for a saved search, by the channels the user chose for it (email, web push,
 * Telegram) and always in the notification center.
 */
class SavedSearchAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<Model>  $listings
     */
    public function __construct(
        public SavedSearch $search,
        public array $listings,
        public int $total,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        $channels = (array) ($this->search->channels ?? []);
        $via = ['database'];

        if (in_array(NotificationChannel::Email->value, $channels, true) && $notifiable->wantsEmailFor(NotificationTopic::SavedSearches)) {
            $via[] = 'mail';
        }

        if (in_array(NotificationChannel::Push->value, $channels, true) && Features::active(SiteFeature::WebPush)) {
            $via[] = WebPushChannel::class;
        }

        if (in_array(NotificationChannel::Telegram->value, $channels, true) && Features::active(SiteFeature::TelegramBot) && $notifiable->hasTelegram()) {
            $via[] = TelegramChannel::class;
        }

        return $via;
    }

    public function toMail(User $notifiable): MailMessage
    {
        $locale = $notifiable->preferredLocale();
        $mail = (new MailMessage)
            ->subject(trans_choice('alerts.mail.subject', $this->total, ['count' => $this->total, 'name' => $this->name()], $locale))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? ''], $locale))
            ->line(trans_choice('alerts.mail.line', $this->total, ['count' => $this->total, 'name' => $this->name()], $locale));

        foreach ($this->listings as $listing) {
            $mail->line('• ['.$this->title($listing, $locale).']('.PublicUrl::for($listing, $locale).')');
        }

        return $mail
            ->action(__('alerts.mail.action', [], $locale), $this->search->resultsUrl(locale: $locale))
            ->line(__('alerts.mail.manage', ['url' => localized_route('account.searches', [], $locale)], $locale));
    }

    /**
     * @return array{title: string, body: string, url: string, tag: string}
     */
    public function toWebPush(User $notifiable): array
    {
        $locale = $notifiable->preferredLocale();
        $first = $this->listings[0] ?? null;

        return [
            'title' => trans_choice('alerts.push.title', $this->total, ['count' => $this->total], $locale),
            'body' => $first !== null ? $this->title($first, $locale).($this->total > 1 ? ' '.__('alerts.push.more', ['count' => $this->total - 1], $locale) : '') : $this->name(),
            'url' => $this->total === 1 && $first !== null ? (string) PublicUrl::for($first, $locale) : $this->search->resultsUrl(locale: $locale),
            'tag' => 'saved-search-'.$this->search->id,
        ];
    }

    /**
     * @return array{html: string, buttons: list<list<array{0: string, 1: string}>>}
     */
    public function toTelegram(User $notifiable): array
    {
        $locale = $notifiable->preferredLocale();
        $lines = ['<b>'.e(trans_choice('alerts.mail.subject', $this->total, ['count' => $this->total, 'name' => $this->name()], $locale)).'</b>', ''];

        foreach ($this->listings as $listing) {
            $lines[] = '• <a href="'.e((string) PublicUrl::for($listing, $locale)).'">'.e($this->title($listing, $locale)).'</a>';
        }

        return [
            'html' => implode("\n", $lines),
            'buttons' => [[[__('alerts.mail.action', [], $locale), $this->search->resultsUrl(locale: $locale)]]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'saved_search_alert',
            'search_id' => $this->search->id,
            'title' => $this->name(),
            'count' => $this->total,
            'url' => $this->search->resultsUrl(),
        ];
    }

    private function name(): string
    {
        return (string) ($this->search->name ?: $this->search->module->label());
    }

    private function title(Model $listing, string $locale): string
    {
        return method_exists($listing, 'getTranslation') ? (string) $listing->getTranslation('title', $locale, true) : '';
    }
}
