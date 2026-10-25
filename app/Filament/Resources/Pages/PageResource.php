<?php

namespace App\Filament\Resources\Pages;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Static pages: about, terms of use, privacy, cookies, contacts.
 */
class PageResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Page::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 40;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.pages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.pages.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(80),
                Toggle::make('is_published')->default(true)->inline(false),
            ]),
            Translatable::tabs([
                'title' => fn (string $name) => TextInput::make($name)->maxLength(160),
                'meta_description' => fn (string $name) => Textarea::make($name)->rows(2)->maxLength(300),
                'body' => fn (string $name) => RichEditor::make($name)
                    ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
            ], required: ['title']),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->getStateUsing(fn (Page $record): string => Translatable::of($record, 'title'))->weight('medium'),
                TextColumn::make('slug')->prefix('/'),
                ToggleColumn::make('is_published'),
                TextColumn::make('updated_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->recordActions([EditAction::make()->slideOver(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePages::route('/')];
    }
}
