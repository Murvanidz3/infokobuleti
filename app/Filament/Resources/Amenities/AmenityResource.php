<?php

namespace App\Filament\Resources\Amenities;

use App\Enums\AmenityScope;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Models\Amenity;
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
use UnitEnum;

class AmenityResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Amenity::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalog;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 30;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.amenities.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.amenities.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs(['name' => fn (string $name) => TextInput::make($name)->maxLength(80)], required: ['name']),
            Grid::make(3)->schema([
                Select::make('scope')->options(AmenityScope::class)->required(),
                TextInput::make('group')->maxLength(32),
                TextInput::make('slug')->required()->maxLength(64),
                TextInput::make('icon')->maxLength(32),
                Toggle::make('is_filterable')->default(true)->inline(false),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->getStateUsing(fn (Amenity $record): string => Translatable::of($record, 'name'))->weight('medium'),
                TextColumn::make('scope')->badge()->color('gray'),
                TextColumn::make('group')->placeholder('—'),
                TextColumn::make('slug')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_filterable')->boolean(),
            ])
            ->filters([SelectFilter::make('scope')->options(AmenityScope::class)])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAmenities::route('/')];
    }
}
