<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Copy of a contact form message for the site's mailbox (the admin keeps the original).
 */
class ContactFormSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contact) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[infokobuleti] '.$this->contact->topic->getLabel().' — '.$this->contact->name)
            ->line($this->contact->message)
            ->line('—')
            ->line($this->contact->name.' · '.collect([$this->contact->email, $this->contact->phone])->filter()->implode(' · '));

        if ($this->contact->email !== null) {
            $mail->replyTo($this->contact->email, $this->contact->name);
        }

        return $mail;
    }
}
