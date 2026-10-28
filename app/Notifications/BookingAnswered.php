<?php

namespace App\Notifications;

use App\Enums\NotificationTopic;
use App\Filament\Support\Translatable;
use App\Models\BookingRequest;
use App\Models\User;
use App\Notifications\Concerns\ChoosesChannels;
use App\Support\Listings\PublicUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a guest that the hotel accepted or declined their booking request. Guests without an
 * account are emailed (on-demand) when they left an address.
 */
class BookingAnswered extends Notification implements ShouldQueue
{
    use ChoosesChannels, Queueable;

    public function __construct(public BookingRequest $booking) {}

    /**
     * @return list<string>
     */
    public function via(User|AnonymousNotifiable $notifiable): array
    {
        return $notifiable instanceof User ? $this->channelsFor($notifiable, NotificationTopic::Inquiries) : ['mail'];
    }

    public function toMail(User|AnonymousNotifiable $notifiable): MailMessage
    {
        $accepted = $this->accepted();
        $mail = (new MailMessage)
            ->subject(__($accepted ? 'account.notifications.types.booking_accepted' : 'account.notifications.types.booking_declined', ['title' => $this->title()]))
            ->greeting(__('moderation.notification.greeting', ['name' => $this->booking->contact_name]))
            ->line(__('account.bookings.mail_dates', [
                'from' => $this->booking->check_in->format('d.m.Y'),
                'to' => $this->booking->check_out->format('d.m.Y'),
            ]));

        if (filled($this->booking->owner_note)) {
            $mail->line(__('account.bookings.note').': '.$this->booking->owner_note);
        }

        $url = PublicUrl::for($this->booking->hotel, $this->booking->locale);

        return $url !== null ? $mail->action(__('account.bookings.open_hotel'), $url) : $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'booking_answered',
            'accepted' => $this->accepted(),
            'booking_id' => $this->booking->getKey(),
            'title' => $this->title(),
            'note' => $this->booking->owner_note,
            'url' => PublicUrl::for($this->booking->hotel, $notifiable->preferredLocale()),
        ];
    }

    private function accepted(): bool
    {
        return $this->booking->status->value === 'accepted';
    }

    private function title(): string
    {
        return Translatable::text($this->booking->hotel->getTranslations('title'));
    }
}
