<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Report;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestReports extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::ModerateContent->value) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.dashboard.latest_reports'))
            ->query(Report::query()->where('status', ReportStatus::Open)->with(['reportable', 'user'])->latest()->limit(6))
            ->columns([
                TextColumn::make('created_at')->label(__('admin.fields.created_at'))->since(),
                TextColumn::make('reportable_type')->label(__('admin.fields.subject'))
                    ->formatStateUsing(fn ($state, Report $record): string => __('admin.morph.'.$state).': '.ReviewResource::subjectTitle($record->reportable))
                    ->limit(48),
                TextColumn::make('reason')->label(__('admin.fields.reason'))->badge()->color('gray'),
            ])
            ->recordUrl(fn (): string => ReportResource::getUrl())
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard.no_reports'));
    }
}
