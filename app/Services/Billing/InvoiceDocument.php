<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Services\DateFormatter;
use App\Services\PriceFormatter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Traits\Localizable;

/**
 * The invoice for a paid order, in the buyer's language: an HTML page (account download, print)
 * and a PDF made from it with dompdf. The PDF needs the dompdf/dompdf package; without it the
 * HTML version is attached instead.
 */
class InvoiceDocument
{
    use Localizable;

    private const string DOMPDF = 'Dompdf\\Dompdf';

    public function __construct(
        private PriceFormatter $price,
        private DateFormatter $dates,
    ) {}

    public function html(Order $order, string $locale): string
    {
        $order->loadMissing(['user', 'pricePlan', 'purchasable']);

        return $this->withLocale($locale, fn (): string => view('billing.invoice', [
            'order' => $order,
            'locale' => $locale,
            'seller' => (array) config('payments.invoice.seller'),
            'amount' => $this->price->format((float) $order->amount, $order->currency->value, $locale),
            'refunded' => (float) $order->refunded_amount > 0 ? $this->price->format((float) $order->refunded_amount, $order->currency->value, $locale) : null,
            'date' => $this->dates->date($order->paid_at ?? $order->created_at ?? now()),
            'title' => $order->title($locale),
            'subject' => $this->subject($order),
            'fonts' => [
                'regular' => (string) config('payments.invoice.font'),
                'bold' => (string) config('payments.invoice.font_bold'),
            ],
        ])->render());
    }

    public function supportsPdf(): bool
    {
        return class_exists(self::DOMPDF);
    }

    /**
     * PDF bytes, or null when dompdf is not installed.
     */
    public function pdf(Order $order, string $locale): ?string
    {
        if (! $this->supportsPdf()) {
            return null;
        }

        $cache = storage_path('framework/dompdf');
        File::ensureDirectoryExists($cache);

        $dompdf = app()->make(self::DOMPDF, ['options' => [
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'InvoiceSans',
            'fontDir' => $cache,
            'fontCache' => $cache,
            'tempDir' => $cache,
            'chroot' => [resource_path('fonts'), $cache],
            'defaultPaperSize' => 'a4',
        ]]);

        $dompdf->loadHtml($this->html($order, $locale), 'UTF-8');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * The attachment for emails and the account download.
     *
     * @return array{content: string, name: string, mime: string}
     */
    public function file(Order $order, string $locale): array
    {
        $name = 'invoice-'.($order->number ?? $order->getKey());
        $pdf = $this->pdf($order, $locale);

        return $pdf !== null
            ? ['content' => $pdf, 'name' => $name.'.pdf', 'mime' => 'application/pdf']
            : ['content' => $this->html($order, $locale), 'name' => $name.'.html', 'mime' => 'text/html'];
    }

    private function subject(Order $order): ?string
    {
        $item = $order->purchasable;

        if ($item === null) {
            return null;
        }

        $title = $item->getAttribute('title');

        return is_string($title) && $title !== '' ? $title : null;
    }
}
