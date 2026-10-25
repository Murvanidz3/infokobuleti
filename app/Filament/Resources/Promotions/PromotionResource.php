<?php

namespace App\Filament\Resources\Promotions;

use App\Enums\Permission;
use App\Enums\PromotionType;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Support\ChecksPermission;
use App\Models\Promotion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * VIP, Super VIP, bumps and homepage placements. Paid ones are created by Phase 8 payments.
 */
class PromotionResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Promotion::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?int $navigationSort = 20;

    public static function requiredPermission(): Permission
    {
        return Permission::ManagePayments;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.promotions.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.promotions.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Select::make('type')->options(PromotionType::class)->required(),
                DateTimePicker::make('starts_at')->seconds(false)->required(),
                DateTimePicker::make('ends_at')->seconds(false)->after('starts_at')->required(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['promotable', 'user']))
            ->columns([
                TextColumn::make('promotable_type')->label(__('admin.fields.subject'))
                    ->formatStateUsing(fn ($state, Promotion $record): string => __('admin.morph.'.$state).': '.ReviewResource::subjectTitle($record->promotable))
                    ->limit(48),
                TextColumn::make('type')->badge(),
                TextColumn::make('user.name')->label(__('admin.fields.owner'))->toggleable(),
                TextColumn::make('starts_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('ends_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('cancelled_at')->dateTime('d.m.Y H:i')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(PromotionType::class),
                Filter::make('active')->label(__('admin.filters.active'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('cancelled_at')->where('starts_at', '<=', now())->where('ends_at', '>', now()))
                    ->default(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('cancel')
                    ->label(__('admin.promotions.cancel'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Promotion $record): bool => $record->cancelled_at === null && $record->ends_at->isFuture())
                    ->action(function (Promotion $record): void {
                        $record->forceFill(['cancelled_at' => now()])->save();

                        Notification::make()->success()->title(__('admin.saved'))->send();
                    }),
            ])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePromotions::route('/')];
    }
}
