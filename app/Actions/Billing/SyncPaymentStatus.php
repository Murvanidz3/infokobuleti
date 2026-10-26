<?php

namespace App\Actions\Billing;

use App\Enums\OrderStatus;
use App\Enums\PaymentEventType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\WalletEntryType;
use App\Exceptions\PaymentGatewayError;
use App\Jobs\FulfillOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Billing\PaymentLog;
use App\Services\Billing\Wallet;
use App\Services\Payments\GatewayStatus;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;

/**
 * Asks the bank for a payment's status and applies it once. The payment row is locked, only
 * forward transitions are applied (PaymentStatus::canBecome), and a paid amount that does not
 * match the order is refused, so repeated or out-of-order callbacks change nothing.
 */
class SyncPaymentStatus
{
    public function __construct(
        private PaymentGatewayManager $gateways,
        private PaymentLog $log,
        private Wallet $wallet,
    ) {}

    /**
     * @throws PaymentGatewayError
     */
    public function handle(Payment $payment): Payment
    {
        $reported = $this->gateways->for($payment->gateway)->fetchStatus($payment);

        $this->log->record(PaymentEventType::StatusCheck, $payment->gateway, $payment, $reported->raw, $reported->status->value);

        return $this->apply($payment, $reported);
    }

    public function apply(Payment $payment, GatewayStatus $reported): Payment
    {
        $fulfil = false;

        $payment = DB::transaction(function () use ($payment, $reported, &$fulfil): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $next = $reported->status;

            if ($next === PaymentStatus::Paid && $reported->paidAmount !== null && round($reported->paidAmount, 2) !== round((float) $locked->amount, 2)) {
                $this->log->record(PaymentEventType::Error, $locked->gateway, $locked, ['message' => 'Paid amount differs from the order', 'reported' => $reported->paidAmount, 'expected' => (float) $locked->amount]);

                return $locked;
            }

            if (! $locked->status->canBecome($next)) {
                return $locked;
            }

            $previous = $locked->status;
            $locked->forceFill(['status' => $next]);

            if ($next === PaymentStatus::Paid) {
                $locked->forceFill([
                    'paid_at' => now(),
                    'payload' => array_filter(['card' => $reported->cardMask, 'gateway_status' => $reported->raw['order_status']['key'] ?? $reported->raw['status'] ?? null]),
                ]);
            }

            if (in_array($next, [PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
                $locked->forceFill(['refunded_amount' => $reported->refundedAmount, 'refunded_at' => now()]);
            }

            $locked->save();

            $this->log->record(PaymentEventType::StatusChanged, $locked->gateway, $locked, ['from' => $previous->value, 'to' => $next->value], $next->value);

            if ($next === PaymentStatus::Paid) {
                $fulfil = $this->markOrderPaid($locked);
            }

            return $locked;
        });

        if ($fulfil) {
            FulfillOrder::dispatch((int) $payment->payable_id);
        }

        return $payment;
    }

    private function markOrderPaid(Payment $payment): bool
    {
        $order = $payment->payable_type === (new Order)->getMorphClass()
            ? Order::query()->whereKey($payment->payable_id)->lockForUpdate()->first()
            : null;

        if ($order === null) {
            return false;
        }

        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Failed, OrderStatus::Cancelled], true)) {
            // The same order was paid twice (two tabs): the second payment goes to the wallet.
            $this->wallet->credit($order->user()->firstOrFail(), (float) $payment->amount, WalletEntryType::Refund, $order, 'duplicate_payment');

            return false;
        }

        $order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now(), 'method' => PaymentMethod::Card, 'cancelled_at' => null])->save();
        $order->assignNumber();

        return true;
    }
}
