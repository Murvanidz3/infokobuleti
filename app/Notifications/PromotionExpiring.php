<?php

namespace App\Notifications;

use App\Enums\NotificationTopic;
use App\Filament\Support\Translatable;
use App\Models\User;
use App\Notifications\Concerns\ChoosesChannels;
use App\Services\DateFormatter;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A VIP / Super VIP / homepage slot / auto-bump ($kind = promotion type) or the business package
 * ($kind = "business_package") ends within a day.
 */
class PromotionExpiring extends Notification implements ShouldQueue
{
    use ChoosesChannels, Queueable;

    public function __construct(
        public string $kind,
        public ?Model $listing,
        public ?CarbonInterface $endsAt,
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
        $locale = $notifiable->preferredLocale();
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject(__('billing.expiring.subject', ['kind' => __('billing.expiring.kinds.'.$this->kind, [], $locale)], $locale))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? ''], $locale))
            ->line(__($this->listing !== null ? 'billing.expiring.line' : 'billing.expiring.line_package', ['title' => $data['title'], 'date' => $data['date']], $locale))
            ->action(__('billing.expiring.action', [], $locale), $this->url($locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'promotion_expiring',
            'kind' => $this->kind,
            'listing_type' => $this->listing?->getMorphClass(),
            'listing_id' => $this->listing?->getKey(),
            'title' => $this->listing !== null && method_exists($this->listing, 'getTranslations') ? Translatable::text($this->listing->getTranslations('title')) : '',
            'date' => $this->endsAt !== null ? app(DateFormatter::class)->date($this->endsAt) : '',
        ];
    }

    private function url(string $locale): string
    {
        return $this->listing !== null
            ? localized_route('account.promote', ['type' => $this->listing->getMorphClass(), 'id' => $this->listing->getKey()], $locale)
            : localized_route('account.billing', [], $locale);
    }
}
