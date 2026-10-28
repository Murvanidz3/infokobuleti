<?php

namespace App\Filament\Resources\SmsCampaigns\RelationManagers;

use App\Enums\SmsStatus;
use App\Filament\Resources\SmsMessages\SmsMessageResource;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Support\GeorgianPhone;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every recipient of a sent campaign with its delivery status.
 */
class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('sms.campaigns.messages');
    }

    /** Shown once the campaign went out. */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof SmsCampaign && ! $ownerRecord->status->isEditable();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user:id,name'))
            ->columns([
                TextColumn::make('phone')->label(__('sms.fields.phone'))->formatStateUsing(fn (string $state): string => GeorgianPhone::format($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => SmsMessageResource::searchPhone($query, $search)),
                TextColumn::make('user.name')->label(__('sms.fields.user'))->placeholder('—'),
                TextColumn::make('status')->label(__('sms.fields.status'))->badge(),
                TextColumn::make('segments')->label(__('sms.fields.segments'))->numeric(),
                TextColumn::make('sent_at')->label(__('sms.fields.sent_at'))->dateTime('d.m.Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('delivered_at')->label(__('sms.fields.delivered_at'))->dateTime('d.m.Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('error')->label(__('sms.fields.error'))->limit(60)->tooltip(fn (SmsMessage $record): ?string => $record->error)->placeholder('—')->color('danger'),
            ])
            ->filters([SelectFilter::make('status')->label(__('sms.fields.status'))->options(SmsStatus::class)])
            ->defaultSort('id');
    }
}
