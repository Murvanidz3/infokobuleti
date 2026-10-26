<?php

namespace App\Actions\Billing;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PricePlanProduct;
use App\Enums\WalletEntryType;
use App\Exceptions\InsufficientBalance;
use App\Exceptions\PurchaseNotAllowed;
use App\Models\Order;
use App\Services\Billing\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Pays an order from the wallet and activates it right away.
 */
class PayFromBalance
{
    public function __construct(
        private Wallet $wallet,
        private ActivatePurchase $activate,
    ) {}

    /**
     * @throws InsufficientBalance
     * @throws PurchaseNotAllowed
     */
    public function handle(Order $order): Order
    {
        if ($order->product === PricePlanProduct::WalletTopUp) {
            throw new PurchaseNotAllowed;
        }

        DB::transaction(function () use ($order): void {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->isOpen()) {
                throw new PurchaseNotAllowed;
            }

            $this->wallet->debit($locked->user()->firstOrFail(), (float) $locked->amount, WalletEntryType::Purchase, $locked);

            $locked->forceFill(['status' => OrderStatus::Paid, 'method' => PaymentMethod::Balance, 'paid_at' => now()])->save();
            $locked->assignNumber();
        });

        return $this->activate->handle($order->refresh());
    }
}
