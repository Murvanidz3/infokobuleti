<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Who did what to a record: staff edits (log "admin") and moderation decisions (log "moderation").
 */
class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activitiesAsSubject';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.activity.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            KeyValueEntry::make('attribute_changes')
                ->label(__('admin.activity.changes'))
                ->getStateUsing(fn (Activity $record): array => self::flattenChanges($record))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label(__('admin.activity.when'))->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('causer.name')->label(__('admin.activity.who'))->placeholder('—'),
                TextColumn::make('log_name')->label(__('admin.activity.log'))->badge(),
                TextColumn::make('event')->label(__('admin.activity.event'))->badge()->color('gray'),
                TextColumn::make('description')->label(__('admin.activity.description'))->limit(60)->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    /**
     * @return array<string, string>
     */
    public static function flattenChanges(Activity $activity): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? $activity->properties?->toArray() ?? [];
        $flat = [];

        foreach ($changes['attributes'] ?? $changes as $key => $value) {
            $old = $changes['old'][$key] ?? null;
            $flat[$key] = ($old !== null ? self::stringify($old).' → ' : '').self::stringify($value);
        }

        return $flat;
    }

    private static function stringify(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
