<?php

namespace App\Notifications;

use App\Filament\Support\Translatable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the owner that their listing was approved or rejected (in the owner's language: User
 * implements HasLocalePreference, so the notification renders in that locale).
 */
class ListingModerated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Model $listing,
        public bool $approved,
        public ?string $reason = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->email !== null ? ['database', 'mail'] : ['database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $key = $this->approved ? 'approved' : 'rejected';

        $mail = (new MailMessage)
            ->subject(__("moderation.notification.{$key}.subject"))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? '']))
            ->line(__("moderation.notification.{$key}.line", ['title' => $this->title()]));

        if (! $this->approved && filled($this->reason)) {
            $mail->line(__('moderation.notification.reason', ['reason' => $this->reason]));
        }

        return $mail->action(__('moderation.notification.action'), localized_route('home'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'listing_moderated',
            'approved' => $this->approved,
            'listing_type' => $this->listing->getMorphClass(),
            'listing_id' => $this->listing->getKey(),
            'title' => $this->title(),
            'reason' => $this->reason,
        ];
    }

    private function title(): string
    {
        return Translatable::text(method_exists($this->listing, 'getTranslations') ? $this->listing->getTranslations('title') : null);
    }
}
