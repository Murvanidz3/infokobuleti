<?php

namespace App\Notifications;

use App\Actions\Privacy\ExportUserData;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * The data export is ready: a signed download link, valid for ExportUserData::DAYS days.
 */
class DataExportReady extends Notification
{
    public function __construct(public string $file) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->email !== null ? ['database', 'mail'] : ['database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $locale = $notifiable->preferredLocale();

        return (new MailMessage)
            ->subject(__('privacy.export.mail_subject', [], $locale))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? ''], $locale))
            ->line(__('privacy.export.mail_line', ['days' => ExportUserData::DAYS], $locale))
            ->action(__('privacy.export.download', [], $locale), $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return ['type' => 'data_export_ready', 'url' => $this->url()];
    }

    private function url(): string
    {
        return URL::temporarySignedRoute('account.privacy.download', now()->addDays(ExportUserData::DAYS), ['file' => $this->file]);
    }
}
