<?php

namespace App\Notifications;

use App\Enums\NotificationTopic;
use App\Filament\Support\Translatable;
use App\Models\User;
use App\Notifications\Concerns\ChoosesChannels;
use App\Services\DateFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * A listing expires in a few days ($expired = false) or has just expired ($expired = true).
 * Both point the owner to one-click renewal in the account.
 */
class ListingExpiry extends Notification implements ShouldQueue
{
    use ChoosesChannels, Queueable;

    public function __construct(
        public Model $listing,
        public bool $expired,
        public ?Carbon $expiresAt = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $this->channelsFor($notifiable, NotificationTopic::Reminders);
    }

    public function toMail(User $notifiable): MailMessage
    {
        $key = $this->expired ? 'expired' : 'expiring';

        return (new MailMessage)
            ->subject(__("account.notifications.types.{$key}", ['title' => $this->title()]))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? '']))
            ->line($this->expired
                ? __('account.notifications.types.expired_text')
                : __('account.notifications.types.expiring_text', ['date' => $this->date()]))
            ->action(__('account.listings.renew'), localized_route('account.listings', ['group' => $this->expired ? 'expired' : 'active'], $notifiable->preferredLocale()));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => $this->expired ? 'listing_expired' : 'listing_expiring',
            'listing_type' => $this->listing->getMorphClass(),
            'listing_id' => $this->listing->getKey(),
            'title' => $this->title(),
            'date' => $this->date(),
        ];
    }

    private function date(): string
    {
        return $this->expiresAt !== null ? app(DateFormatter::class)->date($this->expiresAt) : '';
    }

    private function title(): string
    {
        return Translatable::text(method_exists($this->listing, 'getTranslations') ? $this->listing->getTranslations('title') : null);
    }
}
