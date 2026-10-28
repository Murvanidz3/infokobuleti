<?php

namespace App\Notifications;

use App\Enums\NotificationTopic;
use App\Filament\Support\Translatable;
use App\Models\User;
use App\Models\VacancyApplication;
use App\Notifications\Concerns\ChoosesChannels;
use App\Support\Listings\PublicUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a job applicant that the employer shortlisted, hired or declined them.
 */
class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use ChoosesChannels, Queueable;

    public function __construct(public VacancyApplication $application) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $this->channelsFor($notifiable, NotificationTopic::Inquiries);
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('account.notifications.types.application_status', ['title' => $this->title(), 'status' => $this->application->status->label()]))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? '']))
            ->line(__('account.applications.mail.'.$this->application->status->value, ['title' => $this->title()]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'application_status',
            'status' => $this->application->status->value,
            'application_id' => $this->application->getKey(),
            'title' => $this->title(),
            'url' => PublicUrl::for($this->application->vacancy, $notifiable->preferredLocale()),
        ];
    }

    private function title(): string
    {
        return Translatable::text($this->application->vacancy->getTranslations('title'));
    }
}
