<?php

namespace App\Notifications;

use App\Enums\NotificationTopic;
use App\Filament\Support\Translatable;
use App\Models\User;
use App\Notifications\Concerns\ChoosesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an owner about a message, booking request or job application on one of their items.
 * The inbox and the requests pages arrive with the user account (Phase 7); until then the
 * notification carries everything needed to answer.
 */
class NewInquiry extends Notification implements ShouldQueue
{
    use ChoosesChannels, Queueable;

    public const array KINDS = ['message', 'booking', 'application'];

    /**
     * @param  array<string, string|null>  $details  name, phone, text, dates…
     */
    public function __construct(
        public string $kind,
        public Model $subject,
        public array $details = [],
        public ?string $actionUrl = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $this->channelsFor($notifiable, $this->kind === 'message' ? NotificationTopic::Messages : NotificationTopic::Inquiries);
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__("site.inquiry.{$this->kind}.subject", ['title' => $this->title()]))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? '']))
            ->line(__("site.inquiry.{$this->kind}.line", ['title' => $this->title()]));

        foreach ($this->details as $label => $value) {
            if (filled($value)) {
                $mail->line(__('site.inquiry.fields.'.$label).': '.$value);
            }
        }

        if ($this->actionUrl !== null) {
            $mail->action(__('account.notifications.open'), $this->actionUrl);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'inquiry_'.$this->kind,
            'subject_type' => $this->subject->getMorphClass(),
            'subject_id' => $this->subject->getKey(),
            'title' => $this->title(),
            'details' => $this->details,
            'url' => $this->actionUrl,
        ];
    }

    private function title(): string
    {
        if (method_exists($this->subject, 'getTranslations')) {
            return Translatable::text($this->subject->getTranslations('title') ?: $this->subject->getTranslations('name'));
        }

        return (string) ($this->subject->getAttribute('name') ?? '');
    }
}
