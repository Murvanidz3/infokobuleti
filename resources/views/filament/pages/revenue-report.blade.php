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
            <table class="ik-revenue">
                <thead>
                    <tr>
                        @foreach (['product', 'orders', 'card', 'balance', 'refunded', 'net'] as $column)
                            <th @class(['is-end' => $column !== 'product'])>{{ __('admin.revenue.'.$column) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}@if ($row['product'] === 'wallet_top_up') <span class="ik-delta-note">({{ __('admin.revenue.prepaid') }})</span>@endif</td>
                            <td class="is-end">{{ $row['orders'] }}</td>
                            @foreach (['card', 'balance', 'refunded', 'net'] as $column)
                                <td class="is-end">{{ number_format($row[$column], 2, '.', ' ') }} ₾</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="6">{{ __('admin.revenue.empty') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>{{ __('admin.revenue.total') }}</td>
                        <td class="is-end">{{ $report['totals']['orders'] }}</td>
                        @foreach (['card', 'balance', 'refunded', 'net'] as $column)
                            <td class="is-end">{{ number_format($report['totals'][$column], 2, '.', ' ') }} ₾</td>
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
