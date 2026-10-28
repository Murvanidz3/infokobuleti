<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

/**
 * Background jobs: queue length, failed jobs, Horizon status.
 */
class QueueHealth extends StatsOverviewWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::ManageUsers->value) ?? false;
    }

    protected function getHeading(): ?string
    {
        return __('admin.dashboard.queue_health');
    }

    protected function getStats(): array
    {
        $connection = (string) config('queue.default');

        try {
            $size = $connection === 'sync' ? 0 : Queue::size();
        } catch (Throwable) {
            $size = null;
        }

        $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();

        return [
            Stat::make(__('admin.dashboard.queue_size'), $size === null ? '—' : number_format($size))
                ->description($connection)
                ->icon(Heroicon::OutlinedQueueList)
                ->color($size !== null && $size > 100 ? 'warning' : 'gray'),
            Stat::make(__('admin.dashboard.failed_jobs'), number_format($failed))
                ->description(__('admin.dashboard.last_24h'))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($failed > 0 ? 'danger' : 'success'),
            Stat::make('Horizon', $this->horizonStatus())
                ->icon(Heroicon::OutlinedCpuChip)
                ->color($this->horizonStatus() === __('admin.dashboard.running') ? 'success' : 'gray'),
        ];
    }

    private function horizonStatus(): string
    {
        if (config('queue.default') !== 'redis') {
            return __('admin.dashboard.not_used');
        }

        try {
            $masters = app(MasterSupervisorRepository::class)->all();
        } catch (Throwable) {
            return __('admin.dashboard.unknown');
        }

        if ($masters === []) {
            return __('admin.dashboard.stopped');
        }

        return collect($masters)->every(fn ($master): bool => $master->status === 'paused') ? __('admin.dashboard.paused') : __('admin.dashboard.running');
    }
}
