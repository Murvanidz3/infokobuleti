<?php

namespace App\Filament\Resources\Redirects;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Models\Redirect;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 301 / 302 redirects from old addresses (moved pages, old site links) to new ones.
 */
class RedirectResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Redirect::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static ?int $navigationSort = 30;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageSettings;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.redirects.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.redirects.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)->schema([
                TextInput::make('from_path')->label(__('admin.redirects.from'))->helperText(__('admin.redirects.from_help'))
                    ->required()->maxLength(500)
                    ->rule(fn (?Redirect $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        $path = Redirect::normalize((string) $value);

                        if ($path === '/' || str_starts_with($path, '/admin') || str_starts_with($path, '/livewire')) {
                            $fail(__('admin.redirects.reserved'));
                        }

                        if (Redirect::query()->where('from_path', $path)->when($record, fn ($query) => $query->whereKeyNot($record->id))->exists()) {
                            $fail(__('admin.redirects.exists'));
                        }
                    }),
                TextInput::make('to_url')->label(__('admin.redirects.to'))->helperText(__('admin.redirects.to_help'))
                    ->required()->maxLength(1000)->regex('#^(/|https?://)#i')
                    ->different('from_path'),
                Select::make('status_code')->label(__('admin.redirects.code'))
                    ->options([301 => __('admin.redirects.permanent'), 302 => __('admin.redirects.temporary')])
                    ->default(301)->required()->native(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_path')->label(__('admin.redirects.from'))->searchable()->weight('medium')->copyable(),
                TextColumn::make('to_url')->label(__('admin.redirects.to'))->searchable()->limit(60),
                TextColumn::make('status_code')->label(__('admin.redirects.code'))->badge()->color(fn (int $state): string => $state === 301 ? 'success' : 'warning'),
                TextColumn::make('hits')->label(__('admin.redirects.hits'))->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label(__('admin.redirects.last_hit'))->since()->placeholder('—')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageRedirects::route('/')];
    }
}
