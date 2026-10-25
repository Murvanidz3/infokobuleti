<?php

namespace App\Filament\Resources\Classifieds;

use App\Enums\CategoryModule;
use App\Enums\Currency;
use App\Enums\ItemCondition;
use App\Filament\Resources\ListingResource;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\Category;
use App\Models\Classified;
use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassifiedResource extends ListingResource
{
    protected static ?string $model = Classified::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 30;

    protected static function langKey(): string
    {
        return 'classifieds';
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
                    self::categorySelect(CategoryModule::Classifieds),
                    TextInput::make('price')->numeric()->minValue(0),
                    Select::make('currency')->options(Currency::class)->default(Currency::GEL->value),
                    Toggle::make('negotiable')->inline(false),
                    Select::make('condition')->options(ItemCondition::class),
                    TextInput::make('contact_name')->maxLength(120),
                    TextInput::make('contact_phone')->tel(),
                    KeyValue::make('attribute_values')->columnSpanFull(),
                ])
                ->columns(4),
            ListingAdmin::ownerSection(),
            ListingAdmin::locationSection(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return ListingAdmin::table($table, [
            TextColumn::make('category_id')->label(__('admin.fields.category'))
                ->formatStateUsing(fn ($state, Classified $record): string => Translatable::of($record->category, 'name'))
                ->toggleable(),
            TextColumn::make('price')->formatStateUsing(fn ($state, Classified $record): string => $state === null ? '—' : number_format((float) $state, 0, '.', ' ').' '.$record->currency->value)->sortable(),
        ], [
            SelectFilter::make('category_id')->label(__('admin.fields.category'))
                ->relationship('category', 'slug', fn (Builder $query) => $query->where('module', CategoryModule::Classifieds))
                ->getOptionLabelFromRecordUsing(fn (Category $category): string => Translatable::of($category, 'name')),
        ])->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'media', 'category']));
    }

    protected static function detailEntries(): array
    {
        return [
            Section::make()
                ->schema([
                    TextEntry::make('title')->getStateUsing(fn (Classified $record): string => Translatable::of($record, 'title'))->columnSpanFull(),
                    TextEntry::make('category.slug')->label(__('admin.fields.category'))->formatStateUsing(fn ($state, Classified $record): string => Translatable::of($record->category, 'name')),
                    TextEntry::make('price')->formatStateUsing(fn ($state, Classified $record): string => number_format((float) $state, 0, '.', ' ').' '.$record->currency->value)->placeholder('—'),
                    TextEntry::make('condition')->badge()->placeholder('—'),
                    TextEntry::make('user.name')->label(__('admin.fields.owner')),
                    KeyValueEntry::make('attribute_values')->columnSpanFull(),
                    TextEntry::make('description')->getStateUsing(fn (Classified $record): string => Translatable::of($record, 'description'))->columnSpanFull()->prose(),
                ])
                ->columns(4),
        ];
    }

    public static function categorySelect(CategoryModule $module): Select
    {
        return Select::make('category_id')
            ->relationship('category', 'slug', fn (Builder $query) => $query->where('module', $module)->orderBy('sort'))
            ->getOptionLabelFromRecordUsing(fn (Category $category): string => Translatable::of($category, 'name'))
            ->searchable()
            ->preload();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassifieds::route('/'),
            'create' => Pages\CreateClassified::route('/create'),
            'view' => Pages\ViewClassified::route('/{record}'),
            'edit' => Pages\EditClassified::route('/{record}/edit'),
        ];
    }
}
