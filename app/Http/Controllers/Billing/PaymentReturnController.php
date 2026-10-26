<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\SyncPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayError;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Where the bank sends the payer back. The status is checked with the bank right away (the
 * callback may still be on its way); a payment still in progress shows a page that re-checks.
 */
class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, Payment $payment, SyncPaymentStatus $sync): RedirectResponse|View
    {
        abort_unless((int) $payment->user_id === (int) $request->user()?->getKey(), 404);

        if ($payment->status === PaymentStatus::Pending && $payment->gateway_order_id !== null) {
            try {
                $payment = $sync->handle($payment);
            } catch (PaymentGatewayError $e) {
                report($e);
            }
        }

        $order = $payment->payable instanceof Order ? $payment->payable->refresh() : null;

        return match (true) {
            $payment->status === PaymentStatus::Paid => redirect($this->destination($order))
                ->with('status', __($order?->status === OrderStatus::Fulfilled ? 'billing.return.success' : 'billing.return.processing_done')),
            $payment->status === PaymentStatus::Pending => view('pages.billing.processing', ['payment' => $payment, 'retry' => localized_route('payments.return', ['payment' => $payment->getKey()])]),
            default => redirect($this->destination($order))->with('error', __('billing.return.failed')),
        };
    }

    private function destination(?Order $order): string
    {
        if ($order !== null && $order->purchasable_type !== null && $order->product->isListingPromotion()) {
            return localized_route('account.promote', ['type' => $order->purchasable_type, 'id' => $order->purchasable_id]);
        }

        return localized_route('account.billing');
    }
}
