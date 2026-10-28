<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\DailySeries;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RegistrationsChart extends ChartWidget
{
    use DailySeries;

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '220px';

    public function getHeading(): string
    {
        return __('admin.dashboard.registrations');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = $this->series(DB::table('users')->whereNull('deleted_at'));

        return [
            'labels' => $series['labels'],
            'datasets' => [[
                'label' => __('admin.dashboard.registrations'),
                'data' => $series['values'],
                'borderColor' => '#0a1a2f',
                'backgroundColor' => 'rgba(10, 26, 47, 0.08)',
                'fill' => true,
                'tension' => 0.35,
            ]],
        ];
    }
}
