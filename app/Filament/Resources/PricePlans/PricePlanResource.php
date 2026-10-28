<?php

namespace App\Filament\Resources\PricePlans;

use App\Enums\Currency;
use App\Enums\ListingModule;
use App\Enums\Permission;
use App\Enums\PricePlanProduct;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Models\PricePlan;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The price list for VIP, bumps, business packages and banner slots (used by payments in Phase 8).
 */
class PricePlanResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = PricePlan::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 30;

    public static function requiredPermission(): Permission
    {
        return Permission::ManagePayments;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.price_plans.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.price_plans.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs([
                'name' => fn (string $name) => TextInput::make($name)->maxLength(80),
                'description' => fn (string $name) => Textarea::make($name)->rows(2),
            ], required: ['name']),
            Grid::make(4)->schema([
                Select::make('product')->options(PricePlanProduct::planOptions())->required(),
                Select::make('module')->options(ListingModule::class)->placeholder(__('admin.price_plans.all_modules')),
                TextInput::make('code')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(48),
                TextInput::make('duration_days')->numeric()->minValue(1),
                TextInput::make('price')->numeric()->minValue(0)->required(),
                Select::make('currency')->options(Currency::class)->default(Currency::GEL->value)->required(),
                TextInput::make('listings_limit')->numeric()->minValue(1),
                Toggle::make('is_active')->default(true)->inline(false),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->getStateUsing(fn (PricePlan $record): string => Translatable::of($record, 'name'))->weight('medium'),
                TextColumn::make('product')->badge()->color('gray'),
                TextColumn::make('module')->badge()->color('gray')->placeholder(__('admin.price_plans.all_modules')),
                TextColumn::make('duration_days')->suffix(fn (): string => ' '.__('admin.price_plans.days'))->placeholder('—'),
                TextColumn::make('price')->formatStateUsing(fn ($state, PricePlan $record): string => number_format((float) $state, 2).' '.$record->currency->value)->sortable(),
                ToggleColumn::make('is_active'),
            ])
            ->filters([SelectFilter::make('product')->options(PricePlanProduct::planOptions())])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePricePlans::route('/')];
    }
}
