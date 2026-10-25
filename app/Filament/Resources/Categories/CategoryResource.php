<?php

namespace App\Filament\Resources\Categories;

use App\Enums\CategoryModule;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Models\Category;
use App\Support\AttributeSchema;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Category trees per module, ordered by drag and drop, each with its own attribute schema
 * (the extra fields a classified of that category asks for).
 */
class CategoryResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Category::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalog;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 10;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.categories.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.categories.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs(['name' => fn (string $name) => TextInput::make($name)->maxLength(80)], required: ['name']),
            Section::make(__('admin.sections.details'))
                ->schema([
                    Select::make('module')->options(CategoryModule::class)->required()->live(),
                    Select::make('parent_id')
                        ->relationship('parent', 'slug', fn (Builder $query, callable $get) => $query->whereNull('parent_id')->where('module', $get('module')))
                        ->getOptionLabelFromRecordUsing(fn (Category $category): string => Translatable::of($category, 'name')),
                    TextInput::make('slug')->helperText(__('admin.categories.slug_help'))->maxLength(80),
                    TextInput::make('icon')->maxLength(32),
                    Toggle::make('is_active')->default(true)->inline(false),
                ])
                ->columns(5),
            Section::make(__('admin.fields.attribute_schema'))
                ->description(__('admin.categories.schema_help'))
                ->schema([
                    Repeater::make('attribute_schema')
                        ->hiddenLabel()
                        ->schema([
                            Grid::make(4)->schema([
                                TextInput::make('key')->required()->alphaDash()->maxLength(40),
                                Select::make('type')->options(array_combine(AttributeSchema::TYPES, array_map(fn (string $type): string => __('admin.categories.types.'.$type), AttributeSchema::TYPES)))->required()->live(),
                                TextInput::make('unit')->maxLength(12),
                                Grid::make(2)->schema([
                                    Toggle::make('required')->inline(false),
                                    Toggle::make('filterable')->inline(false),
                                ])->columnSpan(1),
                            ]),
                            Grid::make(3)->schema(array_map(
                                fn (string $locale) => TextInput::make("label.{$locale}")->label(__('admin.fields.label').' '.strtoupper($locale))->required($locale === 'ka'),
                                Translatable::LOCALES,
                            )),
                            Repeater::make('options')
                                ->schema([
                                    TextInput::make('value')->required()->maxLength(40),
                                    ...array_map(fn (string $locale) => TextInput::make("label.{$locale}")->label(strtoupper($locale))->required($locale === 'ka'), Translatable::LOCALES),
                                ])
                                ->columns(4)
                                ->visible(fn (callable $get): bool => in_array($get('type'), ['select', 'multiselect'], true))
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['label']['ka'] ?? $state['value'] ?? null),
                        ])
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['label']['ka'] ?? $state['key'] ?? null)
                        ->reorderable()
                        ->defaultItems(0),
                ])
                ->visible(fn (callable $get): bool => $get('module') === CategoryModule::Classifieds->value || $get('module') === CategoryModule::Classifieds)
                ->collapsible(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('parent')->withCount('children'))
            ->columns([
                TextColumn::make('name')
                    ->getStateUsing(fn (Category $record): string => ($record->parent_id ? '— ' : '').Translatable::of($record, 'name'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereRaw("name->>'ka' ilike ?", ["%{$search}%"])->orWhereRaw("name->>'en' ilike ?", ["%{$search}%"]))
                    ->weight(fn (Category $record): ?string => $record->parent_id ? null : 'semibold'),
                TextColumn::make('module')->badge()->color('gray'),
                TextColumn::make('parent.slug')->label(__('admin.fields.parent'))->formatStateUsing(fn ($state, Category $record): string => Translatable::of($record->parent, 'name'))->placeholder('—'),
                TextColumn::make('children_count')->label(__('admin.fields.children'))->counts('children'),
                TextColumn::make('attribute_schema')->label(__('admin.fields.attribute_schema'))->getStateUsing(fn (Category $record): int => count($record->attribute_schema ?? [])),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('module')->options(CategoryModule::class),
                SelectFilter::make('parent_id')->label(__('admin.fields.parent'))
                    ->relationship('parent', 'slug', fn (Builder $query) => $query->whereNull('parent_id'))
                    ->getOptionLabelFromRecordUsing(fn (Category $category): string => Translatable::of($category, 'name')),
            ])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
