<?php

namespace App\Filament\Support;

use App\Actions\Moderation\ModerateListing;
use App\Enums\ListingStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Building blocks shared by every listing module in the admin (hotels, properties, classifieds,
 * vacancies, places, articles, events): columns, filters, moderation actions, owner and location
 * form sections.
 */
final class ListingAdmin
{
    /**
     * The standard listing table with module-specific columns and filters in the middle.
     *
     * @param  list<Column>  $columns
     * @param  list<BaseFilter>  $filters
     */
    public static function table(Table $table, array $columns = [], array $filters = []): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'media']))
            ->columns([...self::leadingColumns(), ...$columns, ...self::trailingColumns()])
            ->filters([...self::filters(), ...$filters])
            ->recordActions(self::recordActions())
            ->toolbarActions([
                BulkActionGroup::make([
                    self::bulkApproveAction(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    /**
     * @return list<Column>
     */
    public static function leadingColumns(): array
    {
        return [
            ImageColumn::make('cover')
                ->label('')
                ->getStateUsing(fn (Model $record): ?string => method_exists($record, 'coverUrl') ? $record->coverUrl('thumb') : null)
                ->imageSize(44)
                ->square()
                ->extraImgAttributes(['class' => 'rounded-lg object-cover', 'loading' => 'lazy']),
            TextColumn::make('title')
                ->getStateUsing(fn (Model $record): string => Translatable::of($record, 'title'))
                ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(
                    fn (Builder $q) => $q->whereRaw("title->>'ka' ilike ?", ["%{$search}%"])
                        ->orWhereRaw("title->>'en' ilike ?", ["%{$search}%"])
                        ->orWhereRaw("title->>'ru' ilike ?", ["%{$search}%"])
                        ->orWhere('slug', 'ilike', "%{$search}%"),
                ))
                ->limit(48)
                ->tooltip(fn (Model $record): string => Translatable::of($record, 'title'))
                ->weight('medium'),
        ];
    }

    /**
     * @return list<Column>
     */
    public static function trailingColumns(): array
    {
        return [
            TextColumn::make('user.name')
                ->label(__('admin.fields.owner'))
                ->formatStateUsing(fn ($state, Model $record): string => $state ?? (string) ModerateListing::owner($record)?->phone)
                ->searchable()
                ->toggleable(),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('risk_score')
                ->badge()
                ->color(fn (?int $state): string => match (true) {
                    $state === null => 'gray',
                    $state >= 70 => 'danger',
                    $state >= 40 => 'warning',
                    default => 'success',
                })
                ->placeholder('—')
                ->sortable()
                ->toggleable(),
            TextColumn::make('views_count')->numeric()->sortable()->toggleable(),
            TextColumn::make('published_at')->dateTime('d.m.Y H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return list<BaseFilter>
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('status')->options(ListingStatus::class)->multiple(),
            TrashedFilter::make(),
        ];
    }

    /**
     * @return list<Action|ActionGroup>
     */
    public static function recordActions(): array
    {
        return [
            self::approveAction(),
            self::rejectAction(),
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                self::changeStatusAction(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]),
        ];
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label(__('admin.moderation.approve'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Model $record): bool => in_array(ModerateListing::status($record), [ListingStatus::Pending, ListingStatus::Draft], true))
            ->action(function (Model $record): void {
                app(ModerateListing::class)->approve($record, self::moderator());

                Notification::make()->success()->title(__('admin.moderation.approved'))->send();
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('admin.moderation.reject'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Model $record): bool => ModerateListing::status($record) === ListingStatus::Pending)
            ->modalHeading(__('admin.moderation.reject_heading'))
            ->modalSubmitActionLabel(__('admin.moderation.reject'))
            ->schema(self::rejectionForm())
            ->action(function (Model $record, array $data): void {
                app(ModerateListing::class)->reject($record, self::moderator(), $data['template'] ?? null, $data['note'] ?? null);

                Notification::make()->success()->title(__('admin.moderation.rejected'))->send();
            });
    }

    /**
     * @return list<Field>
     */
    public static function rejectionForm(): array
    {
        return [
            Select::make('template')
                ->label(__('admin.moderation.reason_template'))
                ->options(self::reasonOptions())
                ->required(fn (callable $get): bool => blank($get('note')))
                ->live(),
            Textarea::make('note')
                ->label(__('admin.moderation.reason_note'))
                ->rows(3)
                ->maxLength(1000),
        ];
    }

    public static function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label(__('admin.moderation.change_status'))
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->schema(fn (Model $record): array => [
                Select::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(self::transitionOptions($record))
                    ->required(),
                Textarea::make('reason')->label(__('admin.moderation.reason_note'))->rows(2),
            ])
            ->action(function (Model $record, array $data): void {
                try {
                    ModerateListing::listing($record)->transitionTo(ListingStatus::from($data['status']), $data['reason'] ?? null, self::moderator());
                } catch (InvalidStatusTransition $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title(__('admin.saved'))->send();
            });
    }

    public static function bulkApproveAction(): BulkAction
    {
        return BulkAction::make('approveSelected')
            ->label(__('admin.moderation.approve_selected'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $records->filter(fn (Model $record): bool => in_array(ModerateListing::status($record), [ListingStatus::Pending, ListingStatus::Draft], true))
                    ->each(fn (Model $record) => app(ModerateListing::class)->approve($record, self::moderator()));

                Notification::make()->success()->title(__('admin.moderation.approved'))->send();
            });
    }

    /**
     * Owner and business profile of a listing.
     */
    public static function ownerSection(): Section
    {
        return Section::make(__('admin.sections.owner'))
            ->schema([
                Select::make('user_id')
                    ->label(__('admin.fields.owner'))
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $user): string => $user->name ?? (string) $user->phone)
                    ->searchable(['name', 'phone', 'email'])
                    ->required(),
                Select::make('business_profile_id')
                    ->relationship('businessProfile', 'name')
                    ->searchable()
                    ->preload(false),
            ])
            ->columns(2);
    }

    /**
     * Location, map coordinates and the translatable street address.
     */
    public static function locationSection(): Section
    {
        return Section::make(__('admin.sections.location'))
            ->schema([
                Grid::make(3)->schema([
                    Select::make('location_id')
                        ->relationship('location', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Model $location): string => Translatable::of($location, 'name'))
                        ->searchable()
                        ->preload(),
                    TextInput::make('lat')->numeric()->minValue(41)->maxValue(42.5),
                    TextInput::make('lng')->numeric()->minValue(41)->maxValue(42.5),
                ]),
                Translatable::tabs(['address' => fn (string $name) => TextInput::make($name)->maxLength(255)]),
            ])
            ->collapsible();
    }

    /**
     * Read-only moderation details for view pages.
     */
    public static function moderationSection(): Section
    {
        return Section::make(__('admin.sections.moderation'))
            ->schema([
                TextEntry::make('status')->badge(),
                TextEntry::make('risk_score')->placeholder('—'),
                TextEntry::make('published_at')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('expires_at')->dateTime('d.m.Y H:i')->placeholder('—')
                    ->visible(fn (Model $record): bool => array_key_exists('expires_at', $record->getAttributes())),
                TextEntry::make('moderated_at')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
            ])
            ->columns(3);
    }

    /**
     * @return array<string, string>
     */
    public static function reasonOptions(): array
    {
        $options = [];

        foreach ((array) config('moderation.reasons') as $key) {
            $options[(string) $key] = (string) __('moderation.reasons.'.$key);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function transitionOptions(Model $record): array
    {
        $options = [];

        foreach (ModerateListing::status($record)?->allowedTransitions() ?? [] as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    private static function moderator(): User
    {
        /** @var User */
        return auth()->user();
    }
}
