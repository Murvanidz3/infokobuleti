<?php

namespace App\Filament\Resources\Hotels\RelationManagers;

use App\Filament\Support\Translatable;
use App\Models\RoomType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RoomTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'roomTypes';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.room_types.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs([
                'name' => fn (string $name) => TextInput::make($name)->maxLength(120),
                'description' => fn (string $name) => Textarea::make($name)->rows(3),
            ], required: ['name']),
            Grid::make(3)->schema([
                TextInput::make('capacity_adults')->numeric()->minValue(1)->default(2)->required(),
                TextInput::make('capacity_children')->numeric()->minValue(0)->default(0),
                TextInput::make('size_m2')->numeric()->minValue(0)->suffix('m²'),
                TextInput::make('base_price')->numeric()->minValue(0)->prefix('₾')->required(),
                TextInput::make('units')->numeric()->minValue(1)->default(1)->required(),
                TextInput::make('sort')->numeric()->default(0),
            ]),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->getStateUsing(fn (RoomType $record): string => Translatable::of($record, 'name')),
                TextColumn::make('capacity_adults'),
                TextColumn::make('base_price')->money('GEL')->sortable(),
                TextColumn::make('units'),
            ])
            ->defaultSort('sort')
            ->reorderable('sort')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
