<?php

namespace App\Services\Billing;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PricePlanProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Revenue by product for a period (by payment date). Sales paid from the balance are counted
 * under their product; wallet top-ups are money received in advance and shown on their own row,
 * so "card received" is what actually came in through the banks.
 */
class RevenueReport
{
    /**
     * @return array{rows: list<array{product: string, label: string, orders: int, card: float, balance: float, refunded: float, net: float}>, totals: array{orders: int, card: float, balance: float, refunded: float, net: float}, card_received: float, card_refunded: float}
     */
    public function build(Carbon $from, Carbon $to): array
    {
        $settled = [OrderStatus::Paid->value, OrderStatus::Fulfilled->value, OrderStatus::PartiallyRefunded->value, OrderStatus::Refunded->value];

        $rows = DB::table('orders')
            ->whereIn('status', $settled)
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('product')
            ->selectRaw('product, count(*) as orders')
            ->selectRaw('coalesce(sum(amount) filter (where method = ?), 0) as card', [PaymentMethod::Card->value])
            ->selectRaw('coalesce(sum(amount) filter (where method = ?), 0) as balance', [PaymentMethod::Balance->value])
            ->selectRaw('coalesce(sum(refunded_amount), 0) as refunded')
            ->get()
            ->keyBy('product');

        $result = [];
        $totals = ['orders' => 0, 'card' => 0.0, 'balance' => 0.0, 'refunded' => 0.0, 'net' => 0.0];

        foreach (PricePlanProduct::cases() as $product) {
            $row = $rows->get($product->value);

            if ($row === null) {
                continue;
            }

            $line = [
                'product' => $product->value,
                'label' => $product->label(),
                'orders' => (int) $row->orders,
                'card' => round((float) $row->card, 2),
                'balance' => round((float) $row->balance, 2),
                'refunded' => round((float) $row->refunded, 2),
                'net' => round((float) $row->card + (float) $row->balance - (float) $row->refunded, 2),
            ];
            $result[] = $line;

            // Top-ups are prepayments: their money is counted when spent, not twice.
            if ($product !== PricePlanProduct::WalletTopUp) {
                foreach (['orders', 'card', 'balance', 'refunded', 'net'] as $key) {
                    $totals[$key] += $line[$key];
                }
            }
        }

        $payments = DB::table('payments')
            ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value])
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('coalesce(sum(amount), 0) as received, coalesce(sum(refunded_amount), 0) as refunded')
            ->first();

        return [
            'rows' => $result,
            'totals' => array_map(fn ($value) => is_float($value) ? round($value, 2) : $value, $totals),
            'card_received' => round((float) ($payments->received ?? 0), 2),
            'card_refunded' => round((float) ($payments->refunded ?? 0), 2),
        ];
    }

    /**
     * CSV with a header row; amounts use a dot decimal separator.
     */
    public function csv(Carbon $from, Carbon $to): string
    {
        $report = $this->build($from, $to);
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\u{FEFF}");
        fputcsv($handle, [__('admin.revenue.product'), __('admin.revenue.orders'), __('admin.revenue.card'), __('admin.revenue.balance'), __('admin.revenue.refunded'), __('admin.revenue.net')], escape: '\\');

        foreach ($report['rows'] as $row) {
            fputcsv($handle, [$row['label'], $row['orders'], $this->money($row['card']), $this->money($row['balance']), $this->money($row['refunded']), $this->money($row['net'])], escape: '\\');
        }

        $totals = $report['totals'];
        fputcsv($handle, [__('admin.revenue.total'), $totals['orders'], $this->money($totals['card']), $this->money($totals['balance']), $this->money($totals['refunded']), $this->money($totals['net'])], escape: '\\');
        fputcsv($handle, [__('admin.revenue.card_received'), '', $this->money($report['card_received']), '', $this->money($report['card_refunded']), $this->money($report['card_received'] - $report['card_refunded'])], escape: '\\');

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
