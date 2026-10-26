<?php

namespace App\Jobs;

use App\Actions\Billing\ActivatePurchase;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Activates a paid order in the background (queued after the bank confirms the payment).
 */
class FulfillOrder implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public int $orderId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function handle(ActivatePurchase $activate): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order !== null) {
            $activate->handle($order);
        }
    }
}
