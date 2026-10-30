<?php

namespace App\Filament\Pages;

use App\Actions\Moderation\ModerateListing;
use App\Enums\ListingStatus;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\ListingAdmin;
use App\Models\AdminListing;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * One queue for every module: all pending listings, newest first, with quick approve / reject.
 */
class ModerationQueue extends Page implements HasTable
{
    use ChecksPermission, InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Moderation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'moderation';

    /** Morph aliases of the moderated modules. */
    public const array TYPES = ['property', 'project', 'classified', 'vacancy', 'place', 'article', 'event'];

    public static function requiredPermission(): Permission
    {
        return Permission::ModerateContent;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.moderation.queue');
    }

    public function getTitle(): string
    {
        return __('admin.moderation.queue');
    }

    public function getSubheading(): ?string
    {
        return __('admin.moderation.queue_help');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = self::pendingQuery()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    /**
     * @return Builder<AdminListing>
     */
    public static function pendingQuery(): Builder
    {
        return AdminListing::query()->where('status', ListingStatus::Pending->value);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startReview')
                ->label(__('admin.moderation.start_review'))
                ->icon(Heroicon::OutlinedPlay)
                ->keyBindings(['enter'])
                ->visible(fn (): bool => self::pendingQuery()->exists())
                ->url(fn (): string => ModerationReview::getUrl(['key' => self::pendingQuery()->latest('created_at')->value('key')])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::pendingQuery()->with('user'))
            ->columns([
                TextColumn::make('listing_type')->label(__('admin.fields.module'))->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => __('admin.morph.'.$state)),
                TextColumn::make('title')->label(__('admin.fields.title'))
                    ->getStateUsing(fn (AdminListing $record): string => $record->titleText())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(fn (Builder $q) => $q
                        ->whereRaw("title->>'ka' ilike ?", ["%{$search}%"])
                        ->orWhereRaw("title->>'en' ilike ?", ["%{$search}%"])
                        ->orWhereRaw("title->>'ru' ilike ?", ["%{$search}%"])))
                    ->limit(60)
                    ->weight('medium'),
                TextColumn::make('user.name')->label(__('admin.fields.owner'))
                    ->formatStateUsing(fn ($state, AdminListing $record): string => $state ?? (string) $record->user?->phone),
                TextColumn::make('risk_score')->label(__('admin.fields.risk_score'))->badge()->sortable()->placeholder('—')
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 70 => 'danger',
                        $state >= 40 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('created_at')->label(__('admin.fields.submitted'))->since()->sortable()
                    ->tooltip(fn (AdminListing $record): string => $record->created_at->format('d.m.Y H:i')),
            ])
            ->filters([
                SelectFilter::make('listing_type')->label(__('admin.fields.module'))
                    ->options(collect(self::TYPES)->mapWithKeys(fn (string $type): array => [$type => __('admin.morph.'.$type)])),
            ])
            ->recordUrl(fn (AdminListing $record): string => ModerationReview::getUrl(['key' => $record->key]))
            ->recordActions([
                Action::make('approve')
                    ->label(__('admin.moderation.approve'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->action(function (AdminListing $record): void {
                        app(ModerateListing::class)->approve($record->listing(), self::moderator());
                        Notification::make()->success()->title(__('admin.moderation.approved'))->send();
                    }),
                Action::make('reject')
                    ->label(__('admin.moderation.reject'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->modalHeading(__('admin.moderation.reject_heading'))
                    ->modalSubmitActionLabel(__('admin.moderation.reject'))
                    ->schema(ListingAdmin::rejectionForm())
                    ->action(function (AdminListing $record, array $data): void {
                        app(ModerateListing::class)->reject($record->listing(), self::moderator(), $data['template'] ?? null, $data['note'] ?? null);
                        Notification::make()->success()->title(__('admin.moderation.rejected'))->send();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('approveSelected')
                    ->label(__('admin.moderation.approve_selected'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        foreach ($records as $record) {
                            if ($record instanceof AdminListing) {
                                app(ModerateListing::class)->approve($record->listing(), self::moderator());
                            }
                        }
                        Notification::make()->success()->title(__('admin.moderation.approved'))->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.moderation.empty'))
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge)
            ->poll('30s');
    }

    private static function moderator(): User
    {
        /** @var User */
        return auth()->user();
    }
}
