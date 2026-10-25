<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Filament\Widgets\Concerns\DailySeries;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Paid amounts per day (in the payment currency; all prices are GEL until Phase 8).
 */
class RevenueChart extends ChartWidget
{
    use DailySeries;

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '220px';

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::ManagePayments->value) ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.revenue');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = $this->series(DB::table('payments')->where('status', PaymentStatus::Paid->value), 'paid_at', 'sum(amount)');

        return [
            'labels' => $series['labels'],
            'datasets' => [[
                'label' => __('admin.dashboard.revenue'),
                'data' => $series['values'],
                'borderColor' => '#2e7d6b',
                'backgroundColor' => 'rgba(46, 125, 107, 0.10)',
                'fill' => true,
                'tension' => 0.35,
            ]],
        ];
    }
}
