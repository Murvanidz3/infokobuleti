<?php

namespace App\Console\Commands;

use App\Actions\Billing\SyncPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayError;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:reconcile')]
#[Description('Re-check unfinished card payments with the bank (missed callbacks) and close abandoned orders')]
class ReconcilePayments extends Command
{
    /** Staff invoices stay payable this long. */
    public const int INVOICE_DAYS = 30;

    public function handle(SyncPaymentStatus $sync): int
    {
        $timeout = (int) config('payments.pending_timeout_minutes');
        $checked = 0;
        $gaveUp = 0;

        Payment::query()
            ->where('status', PaymentStatus::Pending->value)
            ->whereNotNull('gateway_order_id')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->chunkById(100, function ($payments) use ($sync, $timeout, &$checked, &$gaveUp): void {
                foreach ($payments as $payment) {
                    try {
                        $payment = $sync->handle($payment);
                        $checked++;
                    } catch (PaymentGatewayError $e) {
                        report($e);
                    }

                    // The bank's page has long expired: the payer left without paying.
                    if ($payment->status === PaymentStatus::Pending && $payment->created_at !== null && $payment->created_at->lessThanOrEqualTo(now()->subMinutes($timeout * 4))) {
                        $payment->forceFill(['status' => PaymentStatus::Cancelled])->save();
                        $gaveUp++;
                    }
                }
            });

        $closed = Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->where(fn ($query) => $query
                ->where(fn ($own) => $own->whereNull('issued_by')->where('created_at', '<=', now()->subDay()))
                ->orWhere(fn ($invoice) => $invoice->whereNotNull('issued_by')->where('created_at', '<=', now()->subDays(self::INVOICE_DAYS))))
            ->whereDoesntHave('payments', fn ($payments) => $payments->where('status', PaymentStatus::Pending->value))
            ->update(['status' => OrderStatus::Cancelled->value, 'cancelled_at' => now(), 'updated_at' => now()]);

        $this->info("Checked {$checked} payments, gave up {$gaveUp}, closed {$closed} orders.");

        return self::SUCCESS;
    }
}
