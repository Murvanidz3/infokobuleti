<?php

namespace App\Filament\Resources\Reports;

use App\Enums\Permission;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Support\ChecksPermission;
use App\Models\Report;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Complaints about listings, reviews, profiles or users.
 */
class ReportResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Report::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Moderation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?int $navigationSort = 30;

    public static function requiredPermission(): Permission
    {
        return Permission::ModerateContent;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.reports.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.reports.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $open = Report::query()->where('status', ReportStatus::Open)->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('reportable_type')->label(__('admin.fields.subject'))->formatStateUsing(fn ($state, Report $record): string => __('admin.morph.'.$state).': '.ReviewResource::subjectTitle($record->reportable)),
            TextEntry::make('reason')->badge(),
            TextEntry::make('user.name')->label(__('admin.fields.reporter'))->placeholder('—'),
            TextEntry::make('status')->badge(),
            TextEntry::make('comment')->placeholder('—')->columnSpanFull(),
            TextEntry::make('handler.name')->label(__('admin.fields.handled_by'))->placeholder('—'),
            TextEntry::make('resolution_note')->placeholder('—'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'reportable', 'handler']))
            ->columns([
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('reportable_type')->label(__('admin.fields.subject'))
                    ->formatStateUsing(fn ($state, Report $record): string => __('admin.morph.'.$state).': '.ReviewResource::subjectTitle($record->reportable))
                    ->limit(50),
                TextColumn::make('reason')->badge()->color('gray'),
                TextColumn::make('comment')->limit(60)->wrap()->toggleable(),
                TextColumn::make('user.name')->label(__('admin.fields.reporter'))->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ReportStatus::class)->default(ReportStatus::Open->value),
                SelectFilter::make('reason')->options(ReportReason::class),
            ])
            ->recordActions([
                Action::make('handle')
                    ->label(__('admin.reports.handle'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('primary')
                    ->visible(fn (Report $record): bool => $record->status === ReportStatus::Open)
                    ->schema([
                        Select::make('status')->label(__('admin.fields.status'))->options([
                            ReportStatus::Resolved->value => ReportStatus::Resolved->label(),
                            ReportStatus::Dismissed->value => ReportStatus::Dismissed->label(),
                        ])->required(),
                        Textarea::make('resolution_note')->rows(3),
                    ])
                    ->action(function (Report $record, array $data): void {
                        $record->forceFill([
                            'status' => $data['status'],
                            'resolution_note' => $data['resolution_note'] ?? null,
                            'handled_by' => auth()->id(),
                            'handled_at' => now(),
                        ])->save();

                        Notification::make()->success()->title(__('admin.saved'))->send();
                    }),
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageReports::route('/')];
    }
}
