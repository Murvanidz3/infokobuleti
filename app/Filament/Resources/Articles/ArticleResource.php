<?php

namespace App\Filament\Resources\Articles;

use App\Enums\CategoryModule;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Classifieds\ClassifiedResource;
use App\Filament\Resources\ListingResource;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\Article;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ArticleResource extends ListingResource
{
    protected static ?string $model = Article::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 20;

    protected static function langKey(): string
    {
        return 'articles';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs([
                'title' => fn (string $name) => TextInput::make($name)->maxLength(200),
                'excerpt' => fn (string $name) => Textarea::make($name)->rows(2)->maxLength(400),
                'body' => fn (string $name) => RichEditor::make($name)
                    ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
            ], required: ['title']),
            Section::make(__('admin.sections.details'))
                ->schema([
                    ClassifiedResource::categorySelect(CategoryModule::News),
                    Select::make('user_id')
                        ->label(__('admin.fields.author'))
                        ->relationship('author', 'name', fn (Builder $query) => $query->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', ['super_admin', 'admin', 'moderator'])))
                        ->getOptionLabelFromRecordUsing(fn (User $user): string => $user->name ?? (string) $user->email)
                        ->default(fn (): ?int => auth()->id())
                        ->required(),
                    Toggle::make('is_featured')->inline(false),
                ])
                ->columns(3),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return ListingAdmin::table($table, [
            IconColumn::make('is_featured')->boolean()->sortable(),
        ], [
            TernaryFilter::make('is_featured'),
        ]);
    }

    protected static function detailEntries(): array
    {
        return [
            Section::make()
                ->schema([
                    TextEntry::make('title')->getStateUsing(fn (Article $record): string => Translatable::of($record, 'title'))->columnSpanFull(),
                    TextEntry::make('author.name')->label(__('admin.fields.author')),
                    IconEntry::make('is_featured')->boolean(),
                    TextEntry::make('excerpt')->getStateUsing(fn (Article $record): string => Translatable::of($record, 'excerpt'))->columnSpanFull(),
                    TextEntry::make('body')->getStateUsing(fn (Article $record): string => Translatable::of($record, 'body'))->html()->columnSpanFull()->prose(),
                ])
                ->columns(2),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'view' => Pages\ViewArticle::route('/{record}'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
