<?php

namespace App\Filament\Resources\Hotels;

use App\Enums\AmenityScope;
use App\Enums\HotelType;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Hotels\RelationManagers\BookingRequestsRelationManager;
use App\Filament\Resources\Hotels\RelationManagers\RoomTypesRelationManager;
use App\Filament\Resources\ListingResource;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\Amenity;
use App\Models\Hotel;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HotelResource extends ListingResource
{
    protected static ?string $model = Hotel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 10;

    protected static function langKey(): string
    {
        return 'hotels';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs([
                'title' => fn (string $name) => TextInput::make($name)->maxLength(160),
                'description' => fn (string $name) => Textarea::make($name)->rows(6),
            ], required: ['title']),
            Section::make(__('admin.sections.details'))
                ->schema([
                    Select::make('type')->options(HotelType::class)->required(),
                    Select::make('stars')->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5']),
                    TextInput::make('distance_to_sea_m')->numeric()->minValue(0)->suffix('m'),
                    TextInput::make('rooms_count')->numeric()->minValue(0),
                    TimePicker::make('check_in_from')->seconds(false),
                    TimePicker::make('check_out_until')->seconds(false),
                    Select::make('season_start_month')->options(self::months()),
                    Select::make('season_end_month')->options(self::months()),
                ])
                ->columns(4),
            Section::make(__('admin.sections.contacts'))
                ->schema([
                    TextInput::make('contact_phone')->tel(),
                    TextInput::make('contact_email')->email(),
                    TextInput::make('website')->url(),
                ])
                ->columns(3),
            Section::make(__('admin.fields.amenities'))
                ->schema([
                    CheckboxList::make('amenities')
                        ->hiddenLabel()
                        ->relationship('amenities', 'slug', fn (Builder $query) => $query->where('scope', AmenityScope::Hotel)->orderBy('sort'))
                        ->getOptionLabelFromRecordUsing(fn (Amenity $amenity): string => Translatable::of($amenity, 'name'))
                        ->columns(4),
                ])
                ->collapsible(),
            ListingAdmin::ownerSection(),
            ListingAdmin::locationSection(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return ListingAdmin::table($table, [
            TextColumn::make('type')->badge()->color('gray'),
            TextColumn::make('stars')->sortable()->toggleable(),
            TextColumn::make('price_from')->money('GEL')->sortable(),
            TextColumn::make('distance_to_sea_m')->suffix(' m')->sortable()->toggleable(),
        ], [
            SelectFilter::make('type')->options(HotelType::class),
        ]);
    }

    protected static function detailEntries(): array
    {
        return [
            Section::make()
                ->schema([
                    TextEntry::make('title')->getStateUsing(fn (Hotel $record): string => Translatable::of($record, 'title'))->columnSpanFull(),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('stars')->placeholder('—'),
                    TextEntry::make('price_from')->money('GEL')->placeholder('—'),
                    TextEntry::make('distance_to_sea_m')->suffix(' m')->placeholder('—'),
                    TextEntry::make('user.name')->label(__('admin.fields.owner')),
                    TextEntry::make('contact_phone')->placeholder('—'),
                    TextEntry::make('description')->getStateUsing(fn (Hotel $record): string => Translatable::of($record, 'description'))->columnSpanFull()->prose(),
                ])
                ->columns(3),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RoomTypesRelationManager::class,
            BookingRequestsRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'view' => Pages\ViewHotel::route('/{record}'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function months(): array
    {
        return collect(range(1, 12))->mapWithKeys(fn (int $month): array => [$month => now()->setDay(1)->setMonth($month)->translatedFormat('F')])->all();
    }
}
