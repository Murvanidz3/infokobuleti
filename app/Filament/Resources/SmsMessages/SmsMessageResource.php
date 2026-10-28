<?php

namespace App\Filament\Resources\SmsMessages;

use App\Actions\Sms\SendSingleSms;
use App\Enums\Permission;
use App\Enums\SmsKind;
use App\Enums\SmsStatus;
use App\Filament\NavigationGroup;
use App\Filament\Resources\SmsCampaigns\SmsCampaignResource;
use App\Filament\Support\ChecksPermission;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\DeliveryReports;
use App\Support\GeorgianPhone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * History of every SMS (sign-in codes, single sends, campaigns, tests) with delivery status.
 * Texts of sign-in codes are never stored, so they show as hidden.
 */
class SmsMessageResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = SmsMessage::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Sms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'sms/history';

    public static function requiredPermission(): Permission
    {
        return Permission::ManageSms;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('sms.history.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sms.history.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('sms.history.nav');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label(__('sms.fields.created_at'))->dateTime('d.m.Y H:i:s'),
            TextEntry::make('phone')->label(__('sms.fields.phone'))->formatStateUsing(fn (string $state): string => GeorgianPhone::format($state))->copyable(),
            TextEntry::make('kind')->label(__('sms.fields.kind'))->state(fn (SmsMessage $record): SmsKind => $record->kind())->badge(),
            TextEntry::make('status')->label(__('sms.fields.status'))->badge(),
            TextEntry::make('user.name')->label(__('sms.fields.user'))->placeholder('—'),
            TextEntry::make('sender.name')->label(__('sms.fields.sent_by'))->placeholder('—'),
            TextEntry::make('campaign.name')->label(__('sms.campaigns.singular'))->placeholder('—')
                ->url(fn (SmsMessage $record): ?string => $record->campaign_id !== null ? SmsCampaignResource::getUrl('view', ['record' => $record->campaign_id]) : null),
            TextEntry::make('driver')->label(__('sms.manager.driver')),
            TextEntry::make('segments')->label(__('sms.fields.segments')),
            TextEntry::make('provider_message_id')->label(__('sms.fields.provider_id'))->placeholder('—')->copyable(),
            TextEntry::make('sent_at')->label(__('sms.fields.sent_at'))->dateTime('d.m.Y H:i:s')->placeholder('—'),
            TextEntry::make('delivered_at')->label(__('sms.fields.delivered_at'))->dateTime('d.m.Y H:i:s')->placeholder('—'),
            TextEntry::make('body')->label(__('sms.fields.body'))->state(fn (SmsMessage $record): string => self::text($record))
                ->extraAttributes(['style' => 'white-space: pre-line'])->columnSpanFull(),
            TextEntry::make('error')->label(__('sms.fields.error'))->placeholder('—')->color('danger')->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['campaign:id,name', 'user:id,name', 'sender:id,name']))
            ->columns([
                TextColumn::make('created_at')->label(__('sms.fields.created_at'))->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('phone')->label(__('sms.fields.phone'))->formatStateUsing(fn (string $state): string => GeorgianPhone::format($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::searchPhone($query, $search)),
                TextColumn::make('kind')->label(__('sms.fields.kind'))->state(fn (SmsMessage $record): SmsKind => $record->kind())->badge(),
                TextColumn::make('body')->label(__('sms.fields.body'))->state(fn (SmsMessage $record): string => self::text($record))->limit(60)->wrap()
                    ->color(fn (SmsMessage $record): ?string => $record->isOneTimeCode() ? 'gray' : null),
                TextColumn::make('status')->label(__('sms.fields.status'))->badge(),
                TextColumn::make('campaign.name')->label(__('sms.campaigns.singular'))->placeholder('—')->limit(30)->toggleable()
                    ->url(fn (SmsMessage $record): ?string => $record->campaign_id !== null ? SmsCampaignResource::getUrl('view', ['record' => $record->campaign_id]) : null),
                TextColumn::make('user.name')->label(__('sms.fields.user'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sender.name')->label(__('sms.fields.sent_by'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('segments')->label(__('sms.fields.segments'))->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('delivered_at')->label(__('sms.fields.delivered_at'))->dateTime('d.m.Y H:i')->placeholder('—')->toggleable(),
                TextColumn::make('error')->label(__('sms.fields.error'))->limit(40)->tooltip(fn (SmsMessage $record): ?string => $record->error)->placeholder('—')->color('danger')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('sms.fields.status'))->options(SmsStatus::class)->multiple(),
                SelectFilter::make('kind')->label(__('sms.fields.kind'))->options(SmsKind::class)
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) && ($kind = SmsKind::tryFrom((string) $data['value'])) !== null
                        ? $query->where('purpose', 'like', $kind->purposes())
                        : $query),
                SelectFilter::make('campaign_id')->label(__('sms.campaigns.singular'))
                    ->options(fn (): array => SmsCampaign::query()->latest('id')->limit(200)->pluck('name', 'id')->all())->searchable(),
                Filter::make('period')->label(__('sms.fields.period'))
                    ->schema([
                        DatePicker::make('from')->label(__('sms.fields.from')),
                        DatePicker::make('until')->label(__('sms.fields.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('refresh')
                    ->label(__('sms.actions.refresh_status'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (SmsMessage $record): bool => $record->status === SmsStatus::Sent && filled($record->provider_message_id))
                    ->action(function (SmsMessage $record, DeliveryReports $reports): void {
                        $reports->sync(SmsMessage::query()->whereKey($record->id), limit: 1);
                        Notification::make()->success()->title(__('enums.sms_status.'.$record->fresh()?->status->value))->send();
                    }),
                Action::make('resend')
                    ->label(__('sms.actions.resend'))
                    ->icon(Heroicon::OutlinedArrowUturnRight)
                    ->visible(fn (SmsMessage $record): bool => ! $record->isOneTimeCode() && filled($record->body))
                    ->requiresConfirmation()
                    ->action(function (SmsMessage $record): void {
                        /** @var User $staff */
                        $staff = auth()->user();
                        app(SendSingleSms::class)->handle($record->phone, (string) $record->body, $staff, $record->user);
                        Notification::make()->success()->title(__('sms.notifications.queued', ['phone' => GeorgianPhone::format($record->phone)]))->send();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('refreshSelected')
                    ->label(__('sms.actions.refresh_status'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (Collection $records, DeliveryReports $reports): void {
                        $changed = $reports->sync(SmsMessage::query()->whereKey($records->modelKeys()), limit: 200);
                        Notification::make()->success()->title(__('sms.notifications.refreshed', ['count' => $changed]))->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSmsMessages::route('/')];
    }

    /** The stored text, or a note for sign-in codes (never stored). */
    public static function text(SmsMessage $record): string
    {
        return $record->isOneTimeCode() ? __('sms.history.code_hidden') : (string) ($record->body ?? '—');
    }

    /**
     * Finds "555 12 34 56", "+995555123456" or a part of a number.
     *
     * @param  Builder<SmsMessage>  $query
     * @return Builder<SmsMessage>
     */
    public static function searchPhone(Builder $query, string $search): Builder
    {
        $digits = (string) preg_replace('/\D+/', '', $search);

        return $digits === '' ? $query : $query->where('phone', 'like', '%'.substr($digits, -9).'%');
    }
}
