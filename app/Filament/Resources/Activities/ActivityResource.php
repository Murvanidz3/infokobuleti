<?php

namespace App\Filament\Resources\Activities;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Support\ChecksPermission;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Every staff action and moderation decision, newest first.
 */
class ActivityResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Activity::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 30;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageUsers;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('admin.activity.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.activity.title');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label(__('admin.activity.when'))->dateTime('d.m.Y H:i:s'),
            TextEntry::make('causer.name')->label(__('admin.activity.who'))->placeholder('—'),
            TextEntry::make('subject_type')->label(__('admin.fields.subject'))->formatStateUsing(fn (?string $state, Activity $record): string => $state ? __('admin.morph.'.$state).' #'.$record->subject_id : '—'),
            TextEntry::make('event')->label(__('admin.activity.event'))->badge(),
            TextEntry::make('description')->label(__('admin.activity.description'))->columnSpanFull(),
            KeyValueEntry::make('changes')->label(__('admin.activity.changes'))->getStateUsing(fn (Activity $record): array => ActivitiesRelationManager::flattenChanges($record))->columnSpanFull(),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label(__('admin.activity.when'))->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('causer.name')->label(__('admin.activity.who'))->placeholder('—')->searchable(),
                TextColumn::make('log_name')->label(__('admin.activity.log'))->badge(),
                TextColumn::make('event')->label(__('admin.activity.event'))->badge()->color('gray'),
                TextColumn::make('subject_type')->label(__('admin.fields.subject'))->formatStateUsing(fn (?string $state, Activity $record): string => $state ? __('admin.morph.'.$state).' #'.$record->subject_id : '—'),
                TextColumn::make('description')->label(__('admin.activity.description'))->limit(60),
            ])
            ->filters([
                SelectFilter::make('log_name')->label(__('admin.activity.log'))->options(['admin' => 'admin', 'moderation' => 'moderation']),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageActivities::route('/')];
    }
}
