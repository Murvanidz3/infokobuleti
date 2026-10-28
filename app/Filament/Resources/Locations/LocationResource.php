<?php

namespace App\Filament\Resources\Locations;

use App\Enums\LocationType;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LocationResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Location::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalog;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 20;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.locations.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.locations.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs(['name' => fn (string $name) => TextInput::make($name)->maxLength(80)], required: ['name']),
            Grid::make(3)->schema([
                Select::make('type')->options(LocationType::class)->required(),
                Select::make('parent_id')->relationship('parent', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Location $location): string => Translatable::of($location, 'name')),
                TextInput::make('slug')->maxLength(80),
                TextInput::make('lat')->numeric()->required(),
                TextInput::make('lng')->numeric()->required(),
                Toggle::make('is_active')->default(true)->inline(false),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('parent'))
            ->columns([
                TextColumn::make('name')->getStateUsing(fn (Location $record): string => Translatable::of($record, 'name'))->weight('medium'),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('parent.slug')->label(__('admin.fields.parent'))->formatStateUsing(fn ($state, Location $record): string => Translatable::of($record->parent, 'name'))->placeholder('—'),
                TextColumn::make('lat')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lng')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([SelectFilter::make('type')->options(LocationType::class)])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageLocations::route('/')];
    }
}
