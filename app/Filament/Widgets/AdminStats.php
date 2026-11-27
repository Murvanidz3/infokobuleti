<?php

namespace App\Filament\Widgets;

use App\Contracts\ExchangeRates;
use App\Enums\Currency;
use App\Enums\ListingStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Filament\Pages\ModerationQueue;
use App\Models\AdminListing;
use App\Models\Payment;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\HtmlString;

/**
 * Key numbers: sign-ups, moderation backlog, live listings per module, revenue this month.
 */
class AdminStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        $signups = $this->dailyCounts(User::query()->getQuery()->from('users'));
        $today = (int) $signups[array_key_last($signups)];
        $yesterday = (int) ($signups[array_key_last($signups) - 1] ?? 0);
        $week = (int) array_sum($signups);
        $stats[] = Stat::make(__('admin.dashboard.new_users_today'), number_format($today))
            ->description($this->delta($today, $yesterday, 'admin.dashboard.versus_yesterday', __('admin.dashboard.last_7_days', ['count' => number_format($week)])))
            ->icon(Heroicon::OutlinedUserPlus)
            ->chart($signups)
            ->chartColor('primary');

        $pending = ModerationQueue::pendingQuery()->count();
        $stats[] = Stat::make(__('admin.dashboard.pending'), number_format($pending))
            ->description(__('admin.moderation.queue'))
            ->icon(Heroicon::OutlinedInboxStack)
            ->color($pending > 0 ? 'warning' : 'success')
            ->url(ModerationQueue::canAccess() ? ModerationQueue::getUrl() : null);

        $live = AdminListing::query()
            ->where('status', ListingStatus::Published->value)
            ->selectRaw('listing_type, count(*) as total')
            ->groupBy('listing_type')
            ->pluck('total', 'listing_type');
        $stats[] = Stat::make(__('admin.dashboard.active_listings'), number_format((int) $live->sum()))
            ->description(collect(['hotel', 'property', 'classified', 'vacancy'])->map(fn (string $type): string => __('admin.morph.'.$type).' '.($live[$type] ?? 0))->implode(' · '))
            ->icon(Heroicon::OutlinedSquares2x2)
            ->chart($this->dailyCounts(AdminListing::query()->getQuery()))
            ->chartColor('primary');

        if ($user?->can(Permission::ManagePayments->value)) {
            $thisMonth = $this->revenueSince(now()->startOfMonth());
            $lastMonth = $this->revenueSince(now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth());
            $stats[] = Stat::make(__('admin.dashboard.revenue_month'), number_format($thisMonth, 2).' ₾')
                ->description($this->delta($thisMonth, $lastMonth, 'admin.dashboard.versus_last_month', __('admin.dashboard.revenue_last_month', ['amount' => number_format($lastMonth, 2)])))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->chart($this->dailyCounts(Payment::query()->where('status', PaymentStatus::Paid->value)->getQuery(), 'paid_at'))
                ->chartColor('success');
        }

        return $stats;
    }

    private function revenueSince(\DateTimeInterface $from, ?\DateTimeInterface $until = null): float
    {
        $rates = app(ExchangeRates::class);

        return (float) Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->where('paid_at', '>=', $from)
            ->when($until, fn ($query) => $query->where('paid_at', '<', $until))
            ->get(['amount', 'currency'])
            ->sum(fn (Payment $payment): float => $rates->toGel((float) $payment->amount, $payment->currency ?? Currency::GEL));
    }

    /**
     * A percent pill plus the existing caption. A zero baseline has no meaningful percent.
     */
    private function delta(float $current, float $previous, string $noteKey, string $caption): HtmlString
    {
        $caption = '<span class="ik-delta-note">'.e($caption).'</span>';

        if ($previous <= 0) {
            return new HtmlString($caption);
        }

        $percent = (($current - $previous) / $previous) * 100;
        $class = $percent >= 0 ? 'ik-delta ik-delta-up' : 'ik-delta ik-delta-down';
        $formatted = ($percent >= 0 ? '+' : '').number_format($percent, 1);

        return new HtmlString('<span class="'.$class.'">'.e($formatted).'%</span> '.$caption.' <span class="ik-delta-note">'.e(__($noteKey)).'</span>');
    }

    /**
     * Rows created per day over the last 7 days, for the sparkline.
     *
     * @return list<float>
     */
    private function dailyCounts(Builder $query, string $column = 'created_at'): array
    {
        $column = in_array($column, ['created_at', 'paid_at'], true) ? $column : 'created_at';

        $counts = (clone $query)
            ->where($column, '>=', today()->subDays(6))
            ->selectRaw('date('.$column.') as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(6, 0))->map(fn (int $daysAgo): float => (float) ($counts[today()->subDays($daysAgo)->toDateString()] ?? 0))->all();
    }
}
