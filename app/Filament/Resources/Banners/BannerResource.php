<?php

namespace App\Filament\Resources\Banners;

use App\Enums\BannerSlot;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Models\Banner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Banner::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 50;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.banners.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.banners.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs(['title' => fn (string $name) => TextInput::make($name)->maxLength(120)], required: ['title']),
            Grid::make(2)->schema([
                Select::make('slot')->options(BannerSlot::class)->required(),
                TextInput::make('url')->url()->maxLength(255),
                DateTimePicker::make('starts_at')->seconds(false),
                DateTimePicker::make('ends_at')->seconds(false)->after('starts_at'),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('banners')
                    ->maxSize(4096)
                    ->imageEditor()
                    ->columnSpanFull(),
                Toggle::make('is_active')->default(true)->inline(false),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->disk('public')->imageHeight(40),
                TextColumn::make('title')->getStateUsing(fn (Banner $record): string => Translatable::of($record, 'title'))->weight('medium'),
                TextColumn::make('slot')->badge()->color('gray'),
                TextColumn::make('starts_at')->dateTime('d.m.Y')->placeholder('—'),
                TextColumn::make('ends_at')->dateTime('d.m.Y')->placeholder('—'),
                TextColumn::make('clicks')->numeric(),
                ToggleColumn::make('is_active'),
            ])
            ->filters([SelectFilter::make('slot')->options(BannerSlot::class)])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBanners::route('/')];
    }
}
