<?php

namespace App\Filament\Widgets\Sms;

use App\Enums\SmsStatus;
use App\Filament\Widgets\Concerns\DailySeries;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Messages per day over the last 30 days: all, delivered and failed.
 */
class SmsActivityChart extends ChartWidget
{
    use DailySeries;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '240px';

    public function getHeading(): string
    {
        return __('sms.stats.chart');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $all = $this->series(DB::table('sms_messages'));
        $delivered = $this->series(DB::table('sms_messages')->where('status', SmsStatus::Delivered->value));
        $failed = $this->series(DB::table('sms_messages')->where('status', SmsStatus::Failed->value));

        return [
            'labels' => $all['labels'],
            'datasets' => [
                ['label' => __('sms.stats.all'), 'data' => $all['values'], 'borderColor' => '#0a1a2f', 'backgroundColor' => 'rgba(10, 26, 47, 0.08)', 'fill' => true, 'tension' => 0.35],
                ['label' => __('enums.sms_status.delivered'), 'data' => $delivered['values'], 'borderColor' => '#d4602a', 'tension' => 0.35],
                ['label' => __('enums.sms_status.failed'), 'data' => $failed['values'], 'borderColor' => '#94a3b8', 'borderDash' => [4, 4], 'tension' => 0.35],
            ],
        ];
    }
}
