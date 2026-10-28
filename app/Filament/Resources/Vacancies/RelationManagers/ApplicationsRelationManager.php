<?php

namespace App\Filament\Resources\Vacancies\RelationManagers;

use App\Enums\ApplicationStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.applications.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->options(ApplicationStatus::class)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('user.name')->label(__('admin.fields.applicant'))->searchable(),
                TextColumn::make('contact_phone'),
                TextColumn::make('cover_note')->limit(60)->wrap(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([SelectFilter::make('status')->options(ApplicationStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make()]);
    }
}
