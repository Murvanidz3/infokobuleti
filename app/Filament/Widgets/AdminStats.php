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

        $today = User::query()->where('created_at', '>=', today())->count();
        $week = User::query()->where('created_at', '>=', today()->subDays(6))->count();
        $stats[] = Stat::make(__('admin.dashboard.new_users_today'), number_format($today))
            ->description(__('admin.dashboard.last_7_days', ['count' => number_format($week)]))
            ->icon(Heroicon::OutlinedUserPlus)
            ->chart($this->dailyCounts(User::query()->getQuery()->from('users')));

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
            ->description(collect(['property', 'project', 'classified', 'vacancy'])->map(fn (string $type): string => __('admin.morph.'.$type).' '.($live[$type] ?? 0))->implode(' · '))
            ->icon(Heroicon::OutlinedSquares2x2);

        if ($user?->can(Permission::ManagePayments->value)) {
            $stats[] = Stat::make(__('admin.dashboard.revenue_month'), number_format($this->revenueSince(now()->startOfMonth()), 2).' ₾')
                ->description(__('admin.dashboard.revenue_last_month', ['amount' => number_format($this->revenueSince(now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()), 2)]))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success');
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
     * Rows created per day over the last 7 days, for the sparkline.
     *
     * @return list<float>
     */
    private function dailyCounts(Builder $query): array
    {
        $counts = (clone $query)
            ->where('created_at', '>=', today()->subDays(6))
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(6, 0))->map(fn (int $daysAgo): float => (float) ($counts[today()->subDays($daysAgo)->toDateString()] ?? 0))->all();
    }
}
