<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\DailySeries;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ListingsChart extends ChartWidget
{
    use DailySeries;

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '220px';

    public function getHeading(): string
    {
        return __('admin.dashboard.new_listings');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $series = $this->series(DB::table('admin_listings'));

        return [
            'labels' => $series['labels'],
            'datasets' => [[
                'label' => __('admin.dashboard.new_listings'),
                'data' => $series['values'],
                'backgroundColor' => '#d4602a',
                'borderRadius' => 4,
            ]],
        ];
    }
}
