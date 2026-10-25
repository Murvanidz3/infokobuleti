<?php

namespace App\Filament\Resources\Places;

use App\Enums\PlaceType;
use App\Filament\NavigationGroup;
use App\Filament\Resources\ListingResource;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\Place;
use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PlaceResource extends ListingResource
{
    protected static ?string $model = Place::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 10;

    protected static function langKey(): string
    {
        return 'places';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs([
                'title' => fn (string $name) => TextInput::make($name)->maxLength(160),
                'description' => fn (string $name) => Textarea::make($name)->rows(3),
                'content' => fn (string $name) => Textarea::make($name)->rows(10),
            ], required: ['title']),
            Section::make(__('admin.sections.details'))
                ->schema([
                    Select::make('type')->options(PlaceType::class)->required(),
                    Select::make('price_level')->options([1 => '₾', 2 => '₾₾', 3 => '₾₾₾', 4 => '₾₾₾₾']),
                    TextInput::make('phone')->tel(),
                    TextInput::make('email')->email(),
                    TextInput::make('website')->url()->columnSpan(2),
                    KeyValue::make('opening_hours')->columnSpanFull(),
                ])
                ->columns(4),
            ListingAdmin::ownerSection(),
            ListingAdmin::locationSection(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return ListingAdmin::table($table, [
            TextColumn::make('type')->badge()->color('gray'),
            TextColumn::make('rating_avg')->sortable()->toggleable(),
        ], [
            SelectFilter::make('type')->options(PlaceType::class),
        ]);
    }

    protected static function detailEntries(): array
    {
        return [
            Section::make()
                ->schema([
                    TextEntry::make('title')->getStateUsing(fn (Place $record): string => Translatable::of($record, 'title'))->columnSpanFull(),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('website')->placeholder('—'),
                    TextEntry::make('rating_avg')->placeholder('—'),
                    TextEntry::make('content')->getStateUsing(fn (Place $record): string => Translatable::of($record, 'content'))->columnSpanFull()->prose(),
                ])
                ->columns(4),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaces::route('/'),
            'create' => Pages\CreatePlace::route('/create'),
            'view' => Pages\ViewPlace::route('/{record}'),
            'edit' => Pages\EditPlace::route('/{record}/edit'),
        ];
    }
}
