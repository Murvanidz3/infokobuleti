<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Services\PriceFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Staff issued an invoice (banner slot, special deal) that the user can pay from the account.
 */
class InvoiceIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $locale = $notifiable->preferredLocale();

        return (new MailMessage)
            ->subject(__('billing.invoice_issued.subject', [], $locale))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? ''], $locale))
            ->line(__('billing.invoice_issued.line', ['title' => $this->order->title($locale), 'amount' => $this->amount()], $locale))
            ->action(__('billing.invoice_issued.action', [], $locale), localized_route('account.billing', [], $locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'invoice_issued',
            'order_id' => $this->order->getKey(),
            'title' => $this->order->getTranslations('description'),
            'amount' => $this->amount(),
        ];
    }

    private function amount(): string
    {
        return app(PriceFormatter::class)->format((float) $this->order->amount, $this->order->currency->value);
    }
}
