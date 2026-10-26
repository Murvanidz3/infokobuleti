{{-- Invoice for one paid order: standalone page for printing, and the source of the PDF (dompdf). Colors are the design tokens (navy, coral-pressed, line, secondary). --}}
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('billing.invoice.title') }} {{ $order->number }}</title>
    <style>
        @font-face { font-family: 'InvoiceSans'; font-weight: normal; src: url('{{ $fonts['regular'] }}') format('truetype'); }
        @font-face { font-family: 'InvoiceSans'; font-weight: bold; src: url('{{ $fonts['bold'] }}') format('truetype'); }
        @page { margin: 36px 42px; }
        body { font-family: 'InvoiceSans', 'Noto Sans Georgian', 'Noto Sans', sans-serif; color: #0a1a2f; font-size: 12.5px; line-height: 1.5; margin: 0; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 24px; }
        .head { width: 100%; border-bottom: 2px solid #0a1a2f; padding-bottom: 14px; }
        .logo { font-size: 22px; font-weight: bold; }
        .logo span { color: #bf5323; }
        .muted { color: #3a4a5f; }
        h1 { font-size: 20px; margin: 22px 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        .parties td { vertical-align: top; width: 50%; padding: 14px 0; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #3a4a5f; margin-bottom: 2px; }
        .items th { text-align: left; font-size: 11px; color: #3a4a5f; border-bottom: 1px solid #c9d3df; padding: 8px 6px; }
        .items td { border-bottom: 1px solid #e8edf3; padding: 10px 6px; }
        .num, .items .num { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; font-size: 15px; border-bottom: 0; padding-top: 14px; }
        .badge { display: inline-block; background: #fcece2; color: #bf5323; border-radius: 8px; padding: 2px 8px; font-weight: bold; font-size: 11px; }
        .foot { margin-top: 28px; border-top: 1px solid #e8edf3; padding-top: 12px; font-size: 11px; color: #3a4a5f; }
        @media print { .wrap { padding: 0; } }
    </style>
</head>
<body>
<div class="wrap">
    <table class="head">
        <tr>
            <td class="logo">info<span>kobuleti</span>.com</td>
            <td class="num muted">{{ __('billing.invoice.title') }} <strong style="color:#0a1a2f">{{ $order->number }}</strong><br>{{ $date }}</td>
        </tr>
    </table>

    <h1>{{ __('billing.invoice.heading') }}</h1>
    <div class="muted">{{ __('billing.invoice.status') }}: <span class="badge">{{ $order->status->label() }}</span> · {{ $order->method?->label() }}</div>

    <table class="parties">
        <tr>
            <td>
                <div class="label">{{ __('billing.invoice.seller') }}</div>
                <strong>{{ $seller['name'] ?? '' }}</strong><br>
                @if (! empty($seller['tax_id'])){{ __('billing.invoice.tax_id') }}: {{ $seller['tax_id'] }}<br>@endif
                {{ $seller['address'] ?: __('billing.invoice.seller_address') }}<br>
                @if (! empty($seller['iban'])){{ __('billing.invoice.iban') }}: {{ $seller['iban'] }}<br>@endif
                {{ $seller['email'] ?? '' }}
            </td>
            <td>
                <div class="label">{{ __('billing.invoice.buyer') }}</div>
                <strong>{{ $order->user->name ?: __('billing.invoice.no_name') }}</strong><br>
                @if ($order->user->phone){{ \App\Support\GeorgianPhone::format($order->user->phone) }}<br>@endif
                @if ($order->user->email){{ $order->user->email }}<br>@endif
                {{ __('billing.invoice.customer_id', ['id' => $order->user_id]) }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th>{{ __('billing.invoice.item') }}</th><th class="num">{{ __('billing.invoice.qty') }}</th><th class="num">{{ __('billing.invoice.amount') }}</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $title }}</strong>
                    @if ($subject)<br><span class="muted">{{ $subject }}</span>@endif
                </td>
                <td class="num">1</td>
                <td class="num">{{ $amount }}</td>
            </tr>
            @if ($refunded)
                <tr><td class="muted">{{ __('billing.invoice.refunded') }}</td><td></td><td class="num">− {{ $refunded }}</td></tr>
            @endif
            <tr class="total"><td>{{ __('billing.invoice.total') }}</td><td></td><td class="num">{{ $amount }}</td></tr>
        </tbody>
    </table>

    <div class="foot">{{ __('billing.invoice.footer') }}</div>
</div>
</body>
</html>
