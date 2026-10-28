<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Services\Billing\InvoiceDocument;
use App\Services\PriceFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Receipt for an activated order, with the invoice attached. Always sent by email when the user
 * has an address: it is a transactional message, not a topic users switch off.
 */
class PaymentReceipt extends Notification implements ShouldQueue
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
        $order = $this->order->loadMissing(['pricePlan', 'user', 'purchasable']);
        $invoice = app(InvoiceDocument::class);
        $file = $invoice->file($order, $locale);

        return (new MailMessage)
            ->subject(__('billing.receipt.subject', ['number' => $order->number], $locale))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? ''], $locale))
            ->line(__('billing.receipt.line', ['title' => $order->title($locale), 'amount' => $this->amount()], $locale))
            ->action(__('billing.receipt.action', [], $locale), localized_route('account.billing', [], $locale))
            ->attachData($file['content'], $file['name'], ['mime' => $file['mime']]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'payment_receipt',
            'order_id' => $this->order->getKey(),
            'number' => $this->order->number,
            'title' => $this->order->getTranslations('description') ?: null,
            'product' => $this->order->product->value,
            'amount' => $this->amount(),
        ];
    }

    private function amount(): string
    {
        return app(PriceFormatter::class)->format((float) $this->order->amount, $this->order->currency->value);
    }
}
