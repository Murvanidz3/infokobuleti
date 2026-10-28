<?php

namespace App\Filament\Resources\Hotels\RelationManagers;

use App\Enums\BookingStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BookingRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookingRequests';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.booking_requests.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->options(BookingStatus::class)->required(),
            Textarea::make('owner_note')->rows(3),
        ])->columns(1);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('check_in')->date('d.m.Y'),
            TextEntry::make('check_out')->date('d.m.Y'),
            TextEntry::make('adults'),
            TextEntry::make('children'),
            TextEntry::make('contact_name'),
            TextEntry::make('contact_phone'),
            TextEntry::make('contact_email')->placeholder('—'),
            TextEntry::make('status')->badge(),
            TextEntry::make('message')->placeholder('—')->columnSpanFull(),
            TextEntry::make('owner_note')->placeholder('—')->columnSpanFull(),
        ])->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('contact_name')->searchable(),
                TextColumn::make('contact_phone'),
                TextColumn::make('check_in')->date('d.m.Y')->sortable(),
                TextColumn::make('check_out')->date('d.m.Y'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([SelectFilter::make('status')->options(BookingStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }
}
