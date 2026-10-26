<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Services\DateFormatter;
use App\Services\Monitoring\HealthChecks;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Result of the last monitor:check (every five minutes): disk, memory, queue, backups, SSL.
 */
class SystemHealth extends StatsOverviewWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::ManageSettings->value) ?? false;
    }

    protected function getHeading(): ?string
    {
        return __('monitoring.title');
    }

    protected function getDescription(): ?string
    {
        $at = app(HealthChecks::class)->last()['at'];

        return $at === null ? __('monitoring.never') : __('monitoring.last_check', ['time' => app(DateFormatter::class)->relative($at)]);
    }

    protected function getStats(): array
    {
        return array_map(
            fn (array $check): Stat => Stat::make(__('monitoring.checks.'.$check['key']), $check['value'])
                ->description($check['detail'] ?: null)
                ->color(match ($check['ok']) {
                    true => 'success', false => 'danger', null => 'gray'
                }),
            app(HealthChecks::class)->last()['checks'],
        );
    }
}
