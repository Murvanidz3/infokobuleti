<x-filament-panels::page>
    <x-filament::section>
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
            <label style="display:flex;flex-direction:column;gap:4px">
                <span class="text-sm font-medium">{{ __('admin.revenue.from') }}</span>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
            </label>
            <label style="display:flex;flex-direction:column;gap:4px">
                <span class="text-sm font-medium">{{ __('admin.revenue.to') }}</span>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
            </label>
            @foreach (['month', 'last_month', 'last_30', 'year'] as $preset)
                <x-filament::button color="gray" size="sm" wire:click="preset('{{ $preset }}')">{{ __('admin.revenue.presets.'.$preset) }}</x-filament::button>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section :heading="__('admin.revenue.by_product')">
        <div style="overflow-x:auto">
            <table class="fi-ta-table" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        @foreach (['product', 'orders', 'card', 'balance', 'refunded', 'net'] as $column)
                            <th style="text-align:{{ $column === 'product' ? 'left' : 'right' }};padding:8px 10px;border-bottom:1px solid rgba(127,127,127,.25)" class="text-sm font-semibold">{{ __('admin.revenue.'.$column) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td style="padding:8px 10px" class="text-sm">{{ $row['label'] }}@if ($row['product'] === 'wallet_top_up') <span class="text-xs" style="opacity:.6">({{ __('admin.revenue.prepaid') }})</span>@endif</td>
                            <td style="padding:8px 10px;text-align:right" class="text-sm">{{ $row['orders'] }}</td>
                            @foreach (['card', 'balance', 'refunded', 'net'] as $column)
                                <td style="padding:8px 10px;text-align:right" class="text-sm">{{ number_format($row[$column], 2, '.', ' ') }} ₾</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:16px 10px" class="text-sm">{{ __('admin.revenue.empty') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td style="padding:10px;border-top:1px solid rgba(127,127,127,.25)" class="text-sm font-semibold">{{ __('admin.revenue.total') }}</td>
                        <td style="padding:10px;text-align:right;border-top:1px solid rgba(127,127,127,.25)" class="text-sm font-semibold">{{ $report['totals']['orders'] }}</td>
                        @foreach (['card', 'balance', 'refunded', 'net'] as $column)
                            <td style="padding:10px;text-align:right;border-top:1px solid rgba(127,127,127,.25)" class="text-sm font-semibold">{{ number_format($report['totals'][$column], 2, '.', ' ') }} ₾</td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-sm" style="margin-top:14px">
            {{ __('admin.revenue.card_received') }}: <strong>{{ number_format($report['card_received'], 2, '.', ' ') }} ₾</strong>
            · {{ __('admin.revenue.refunded') }}: {{ number_format($report['card_refunded'], 2, '.', ' ') }} ₾
        </p>
    </x-filament::section>
</x-filament-panels::page>
