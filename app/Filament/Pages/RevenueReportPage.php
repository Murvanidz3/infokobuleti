<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Services\Billing\RevenueReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Revenue by product and period, with a CSV export for the accountant.
 */
class RevenueReportPage extends Page
{
    use ChecksPermission;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'revenue';

    protected string $view = 'filament.pages.revenue-report';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public static function requiredPermission(): Permission
    {
        return Permission::ManagePayments;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.revenue.title');
    }

    public function getTitle(): string
    {
        return __('admin.revenue.title');
    }

    public function mount(): void
    {
        $this->from = $this->validDate($this->from) ?? now()->startOfMonth()->toDateString();
        $this->to = $this->validDate($this->to) ?? now()->toDateString();
    }

    public function preset(string $period): void
    {
        [$from, $to] = match ($period) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'year' => [now()->startOfYear(), now()],
            'last_30' => [now()->subDays(29), now()],
            default => [now()->startOfMonth(), now()],
        };

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('admin.revenue.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (RevenueReport $report): StreamedResponse => response()->streamDownload(
                    function () use ($report): void {
                        echo $report->csv(...$this->period());
                    },
                    'revenue-'.$this->from.'-'.$this->to.'.csv',
                    ['Content-Type' => 'text/csv; charset=UTF-8'],
                )),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['report' => app(RevenueReport::class)->build(...$this->period())];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(): array
    {
        $from = Carbon::parse($this->validDate($this->from) ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($this->validDate($this->to) ?? now()->toDateString());

        return $from->greaterThan($to) ? [$to, $from] : [$from, $to];
    }

    private function validDate(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && strtotime($value) !== false ? $value : null;
    }
}
