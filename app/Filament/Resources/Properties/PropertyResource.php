<?php

namespace App\Filament\Resources\Properties;

use App\Enums\AmenityScope;
use App\Enums\BuildingStatus;
use App\Enums\Currency;
use App\Enums\ListedBy;
use App\Enums\NearbyPlace;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Filament\Resources\ListingResource;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\Amenity;
use App\Models\Property;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
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
use Illuminate\Database\Eloquent\Builder;

class PropertyResource extends ListingResource
{
    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?int $navigationSort = 20;

    protected static function langKey(): string
    {
        return 'properties';
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
                    Select::make('deal')->options(PropertyDeal::class)->required(),
                    Select::make('type')->options(PropertyType::class)->required(),
                    TextInput::make('price')->numeric()->minValue(0)->required(),
                    Select::make('currency')->options(Currency::class)->default(Currency::GEL->value)->required(),
                    TextInput::make('area_m2')->numeric()->minValue(0)->suffix('m²'),
                    TextInput::make('land_area_m2')->numeric()->minValue(0)->suffix('m²'),
                    TextInput::make('rooms')->numeric()->minValue(0),
                    TextInput::make('bedrooms')->numeric()->minValue(0),
                    TextInput::make('bathrooms')->numeric()->minValue(0),
                    TextInput::make('floor')->numeric(),
                    TextInput::make('total_floors')->numeric()->minValue(0),
                    TextInput::make('distance_to_sea_m')->numeric()->minValue(0)->suffix('m'),
                    Select::make('condition')->options(PropertyCondition::class),
                    Select::make('building_status')->options(BuildingStatus::class),
                    Select::make('listed_by')->options(ListedBy::class)->default(ListedBy::Owner->value)->required(),
                    TextInput::make('cadastral_code')->maxLength(32),
                    CheckboxList::make('nearby')->options(NearbyPlace::options())->columns(4)->columnSpanFull(),
                ])
                ->columns(4),
            Section::make(__('admin.sections.contacts'))
                ->schema([
                    TextInput::make('contact_name')->maxLength(120),
                    TextInput::make('contact_phone')->tel(),
                ])
                ->columns(2),
            Section::make(__('admin.fields.features'))
                ->schema([
                    CheckboxList::make('features')
                        ->hiddenLabel()
                        ->relationship('features', 'slug', fn (Builder $query) => $query->where('scope', AmenityScope::Property)->orderBy('sort'))
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
            TextColumn::make('deal')->badge()->color('gray'),
            TextColumn::make('type')->badge()->color('gray')->toggleable(),
            TextColumn::make('price')->formatStateUsing(fn ($state, Property $record): string => number_format((float) $state, 0, '.', ' ').' '.$record->currency->value)->sortable(),
            TextColumn::make('area_m2')->suffix(' m²')->sortable()->toggleable(),
            TextColumn::make('rooms')->sortable()->toggleable(),
        ], [
            SelectFilter::make('deal')->options(PropertyDeal::class),
            SelectFilter::make('type')->options(PropertyType::class),
        ]);
    }

    protected static function detailEntries(): array
    {
        return [
            Section::make()
                ->schema([
                    TextEntry::make('title')->getStateUsing(fn (Property $record): string => Translatable::of($record, 'title'))->columnSpanFull(),
                    TextEntry::make('deal')->badge(),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('price')->formatStateUsing(fn ($state, Property $record): string => number_format((float) $state, 0, '.', ' ').' '.$record->currency->value),
                    TextEntry::make('price_per_m2')->placeholder('—'),
                    TextEntry::make('area_m2')->suffix(' m²')->placeholder('—'),
                    TextEntry::make('rooms')->placeholder('—'),
                    TextEntry::make('user.name')->label(__('admin.fields.owner')),
                    TextEntry::make('contact_phone')->placeholder('—'),
                    TextEntry::make('description')->getStateUsing(fn (Property $record): string => Translatable::of($record, 'description'))->columnSpanFull()->prose(),
                ])
                ->columns(4),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'view' => Pages\ViewProperty::route('/{record}'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
