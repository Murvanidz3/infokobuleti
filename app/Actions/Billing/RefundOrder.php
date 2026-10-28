<?php

namespace App\Actions\Billing;

use App\Enums\OrderStatus;
use App\Enums\PaymentEventType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PricePlanProduct;
use App\Enums\WalletEntryType;
use App\Exceptions\PaymentGatewayError;
use App\Exceptions\PurchaseNotAllowed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OrderRefunded;
use App\Services\Billing\PaymentLog;
use App\Services\Billing\Wallet;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;

/**
 * Staff refund of an order, fully or partly: card payments go back through the bank, balance
 * payments back to the wallet. A full refund also stops what was bought (promotion, package).
 * A wallet top-up can only be refunded while the money is still on the balance.
 */
class RefundOrder
{
    public function __construct(
        private PaymentGatewayManager $gateways,
        private Wallet $wallet,
        private PaymentLog $log,
    ) {}

    /**
     * @throws PurchaseNotAllowed
     * @throws PaymentGatewayError
     */
    public function handle(Order $order, float $amount, User $staff, ?string $reason = null): Order
    {
        $amount = round($amount, 2);

        if (! $order->status->isSettled() || $amount <= 0 || $amount > $order->refundableAmount()) {
            throw new PurchaseNotAllowed;
        }

        if ($order->product === PricePlanProduct::WalletTopUp && $amount > (float) $order->user()->value('balance')) {
            throw new PurchaseNotAllowed;
        }

        $payment = $order->method === PaymentMethod::Card
            ? Payment::query()->whereMorphedTo('payable', $order)->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value])->latest('id')->first()
            : null;

        if ($order->method === PaymentMethod::Card && $payment === null) {
            throw new PurchaseNotAllowed;
        }

        // The bank call happens first and outside the transaction; nothing is changed if it fails.
        if ($payment !== null) {
            try {
                $answer = $this->gateways->for($payment->gateway)->refund($payment, $amount);
            } catch (PaymentGatewayError $e) {
                $this->log->record(PaymentEventType::Error, $payment->gateway, $payment, ['refund' => $amount, 'message' => $e->getMessage()]);

                throw $e;
            }

            $this->log->record(PaymentEventType::Refund, $payment->gateway, $payment, ['amount' => $amount, 'reason' => $reason, 'by' => $staff->getKey(), 'answer' => $answer]);
        }

        DB::transaction(function () use ($order, $payment, $amount, $staff, $reason): void {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $refunded = round((float) $locked->refunded_amount + $amount, 2);
            $full = $refunded >= round((float) $locked->amount, 2);

            if ($payment !== null) {
                $paymentRefunded = round((float) $payment->refunded_amount + $amount, 2);
                $payment->forceFill([
                    'refunded_amount' => $paymentRefunded,
                    'refunded_at' => now(),
                    'status' => $paymentRefunded >= round((float) $payment->amount, 2) ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
                ])->save();
            } else {
                $this->wallet->credit($locked->user()->firstOrFail(), $amount, WalletEntryType::Refund, $locked, $reason, $staff);
            }

            if ($locked->product === PricePlanProduct::WalletTopUp) {
                $this->wallet->debit($locked->user()->firstOrFail(), $amount, WalletEntryType::Refund, $locked, $reason, $staff);
            }

            $locked->forceFill([
                'refunded_amount' => $refunded,
                'refunded_at' => now(),
                'status' => $full ? OrderStatus::Refunded : OrderStatus::PartiallyRefunded,
            ])->save();

            if ($full) {
                $this->stopPurchase($locked);
            }

            activity('billing')->performedOn($locked)->causedBy($staff)->event('refunded')
                ->withProperties(['amount' => $amount, 'reason' => $reason])->log('refunded');

            $order->setRawAttributes($locked->getAttributes(), true);
        });

        $order->loadMissing('user');
        $order->user->notify(new OrderRefunded($order, $amount));

        return $order;
    }

    private function stopPurchase(Order $order): void
    {
        $order->promotions()->whereNull('cancelled_at')->get()->each(fn ($promotion) => $promotion->forceFill(['cancelled_at' => now()])->save());

        $subscription = $order->subscription()->whereNull('cancelled_at')->first();

        if ($subscription !== null) {
            $subscription->forceFill(['cancelled_at' => now()])->save();
            ActivatePurchase::syncPackageFlag($order->user()->firstOrFail());
        }
    }
}
