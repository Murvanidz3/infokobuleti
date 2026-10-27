<?php

namespace App\Filament\Widgets\Sms;

use App\Enums\SmsCampaignStatus;
use App\Enums\SmsStatus;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Services\Sms\ProviderStatus;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * SMS manager header: balance, messages and delivery over the last 30 days, scheduled campaigns.
 */
class SmsStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $provider = app(ProviderStatus::class)->get();
        $since = now()->subDays(30);

        $counts = SmsMessage::query()->where('created_at', '>=', $since)->toBase()
            ->selectRaw('status, count(*) as total, coalesce(sum(segments), 0) as parts')->groupBy('status')->get()->keyBy('status');

        $total = (int) $counts->sum('total');
        $delivered = (int) ($counts[SmsStatus::Delivered->value]->total ?? 0);
        $failed = (int) ($counts[SmsStatus::Failed->value]->total ?? 0);
        $sent = (int) ($counts[SmsStatus::Sent->value]->total ?? 0) + $delivered;
        $parts = (int) $counts->sum('parts');
        $low = $provider['balance'] !== null && $provider['balance'] < (int) config('sms.drivers.ubill.min_balance');

        $scheduled = SmsCampaign::query()->where('status', SmsCampaignStatus::Scheduled)->count();

        return [
            Stat::make(__('sms.stats.balance'), $provider['balance'] === null ? '—' : number_format($provider['balance'], 0, '.', ' ').' SMS')
                ->description($provider['error'] ?? ($provider['balance'] === null ? __('sms.stats.no_balance', ['driver' => $provider['driver']]) : ($low ? __('sms.stats.low_balance') : __('sms.stats.driver', ['driver' => $provider['driver']]))))
                ->color($provider['error'] !== null || $low ? 'danger' : 'success'),
            Stat::make(__('sms.stats.messages_30'), number_format($total, 0, '.', ' '))
                ->description(__('sms.stats.parts', ['count' => number_format($parts, 0, '.', ' ')])),
            Stat::make(__('sms.stats.delivery_rate'), $sent > 0 ? round($delivered / $sent * 100, 1).'%' : '—')
                ->description(__('sms.stats.delivered', ['count' => $delivered]))
                ->color('success'),
            Stat::make(__('sms.stats.failed_30'), (string) $failed)
                ->description($total > 0 ? round($failed / $total * 100, 1).'%' : '—')
                ->color($failed > 0 ? 'danger' : 'gray'),
            Stat::make(__('sms.stats.scheduled'), (string) $scheduled)
                ->description(__('sms.stats.campaigns_total', ['count' => SmsCampaign::query()->count()])),
        ];
    }
}
