<?php

namespace App\Filament\Resources\Conversations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.fields.messages');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user')->withTrashed())
            ->columns([
                TextColumn::make('created_at')->dateTime('d.m.Y H:i'),
                TextColumn::make('user.name')->label(__('admin.fields.author')),
                TextColumn::make('body')->wrap(),
            ])
            ->defaultSort('created_at')
            ->paginated([50, 100]);
    }
}
