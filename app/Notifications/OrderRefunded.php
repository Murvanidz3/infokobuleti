<?php

namespace App\Notifications;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use App\Services\PriceFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRefunded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public float $amount,
    ) {}

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
            ->subject(__('billing.refund.subject', ['number' => $this->order->number], $locale))
            ->greeting(__('moderation.notification.greeting', ['name' => $notifiable->name ?? ''], $locale))
            ->line(__($this->order->method === PaymentMethod::Balance ? 'billing.refund.line_balance' : 'billing.refund.line_card', ['amount' => $this->amountText(), 'number' => $this->order->number], $locale))
            ->action(__('billing.receipt.action', [], $locale), localized_route('account.billing', [], $locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'order_refunded',
            'order_id' => $this->order->getKey(),
            'number' => $this->order->number,
            'amount' => $this->amountText(),
            'to_balance' => $this->order->method === PaymentMethod::Balance,
        ];
    }

    private function amountText(): string
    {
        return app(PriceFormatter::class)->format($this->amount, $this->order->currency->value);
    }
}
