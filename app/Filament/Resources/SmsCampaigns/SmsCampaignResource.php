<?php

namespace App\Filament\Resources\SmsCampaigns;

use App\Enums\AccountType;
use App\Enums\Permission;
use App\Enums\SmsAudience;
use App\Enums\SmsCampaignStatus;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\SmsFields;
use App\Models\SmsCampaign;
use App\Services\Settings;
use App\Services\Sms\Campaigns;
use App\Services\Sms\ProviderStatus;
use App\Support\GeorgianPhone;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Bulk SMS: write the text, pick the recipients (numbers, a CSV file or registered users),
 * review the checked list and cost, then send now or at a set time.
 */
class SmsCampaignResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = SmsCampaign::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Sms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'sms/campaigns';

    public static function requiredPermission(): Permission
    {
        return Permission::ManageSms;
    }

    public static function getModelLabel(): string
    {
        return __('sms.campaigns.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sms.campaigns.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $active = SmsCampaign::query()->whereIn('status', [SmsCampaignStatus::Scheduled, SmsCampaignStatus::Sending])->count();

        return $active > 0 ? (string) $active : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('sms.campaigns.sections.text'))->icon(Heroicon::OutlinedPencilSquare)->schema([
                TextInput::make('name')->label(__('sms.fields.campaign_name'))->required()->maxLength(120)->helperText(__('sms.campaigns.name_help')),
                SmsFields::template(),
                SmsFields::body(placeholders: true),
                Toggle::make('append_footer')->label(__('sms.fields.append_footer'))->default(true)
                    ->helperText(fn (): string => filled($footer = app(Settings::class)->get('sms.campaign_footer'))
                        ? __('sms.campaigns.footer_is', ['footer' => $footer])
                        : __('sms.campaigns.footer_missing')),
            ]),
            Section::make(__('sms.campaigns.sections.recipients'))->icon(Heroicon::OutlinedUsers)->schema([
                ToggleButtons::make('audience')->label(__('sms.fields.audience'))->options(SmsAudience::class)->inline()->required()->default(SmsAudience::Manual->value)->live(),
                Textarea::make('audience_options.numbers')->label(__('sms.fields.numbers'))->rows(6)
                    ->helperText(__('sms.campaigns.numbers_help'))
                    ->visible(fn (Get $get): bool => self::audience($get) === SmsAudience::Manual)
                    ->required(fn (Get $get): bool => self::audience($get) === SmsAudience::Manual),
                FileUpload::make('audience_options.csv')->label(__('sms.fields.csv'))
                    ->disk('local')->directory('sms-imports')->visibility('private')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'text/x-csv'])
                    ->maxSize(5120)
                    ->helperText(__('sms.campaigns.csv_help'))
                    ->visible(fn (Get $get): bool => self::audience($get) === SmsAudience::Csv)
                    ->required(fn (Get $get): bool => self::audience($get) === SmsAudience::Csv),
                Grid::make(['md' => 2, 'xl' => 4])->visible(fn (Get $get): bool => self::audience($get) === SmsAudience::Users)->schema([
                    Select::make('audience_options.account_type')->label(__('sms.fields.account_type'))->options(AccountType::class)->placeholder(__('sms.fields.all')),
                    Select::make('audience_options.locale')->label(__('sms.fields.locale'))->options(fn (): array => collect(['ka', 'en', 'ru'])->mapWithKeys(fn (string $locale): array => [$locale => __('locales.'.$locale, locale: $locale)])->all())->placeholder(__('sms.fields.all')),
                    DatePicker::make('audience_options.registered_from')->label(__('sms.fields.registered_from')),
                    DatePicker::make('audience_options.registered_until')->label(__('sms.fields.registered_until')),
                ]),
                TextEntry::make('users_help')->hiddenLabel()->state(__('sms.campaigns.users_help'))->color('warning')
                    ->visible(fn (Get $get): bool => self::audience($get) === SmsAudience::Users),
                Toggle::make('respect_stop_list')->label(__('sms.fields.respect_stop_list'))->default(true)->helperText(__('sms.campaigns.stop_list_help')),
            ]),
        ])->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('sms.campaigns.sections.summary'))->columns(['md' => 2, 'xl' => 4])->schema([
                TextEntry::make('status')->label(__('sms.fields.status'))->badge(),
                TextEntry::make('recipients_count')->label(__('sms.fields.recipients'))->numeric(),
                TextEntry::make('estimated')->label(__('sms.fields.estimated'))
                    ->state(fn (SmsCampaign $record): string => __('sms.campaigns.estimate', ['total' => $record->estimatedSms(), 'parts' => $record->segments])),
                TextEntry::make('balance')->label(__('sms.stats.balance'))
                    ->state(fn (ProviderStatus $status): string => ($balance = $status->balance()) === null ? '—' : $balance.' SMS')
                    ->color(fn (SmsCampaign $record, ProviderStatus $status): string => ($balance = $status->balance()) !== null && $record->status->isEditable() && $balance < $record->estimatedSms() ? 'danger' : 'gray'),
                TextEntry::make('scheduled_at')->label(__('sms.fields.scheduled_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('started_at')->label(__('sms.fields.started_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('finished_at')->label(__('sms.fields.finished_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('creator.name')->label(__('sms.fields.created_by'))->placeholder('—'),
                TextEntry::make('sent_count')->label(__('sms.fields.sent'))->numeric(),
                TextEntry::make('delivered_count')->label(__('sms.fields.delivered'))
                    ->formatStateUsing(fn (int $state, SmsCampaign $record): string => $state.(($rate = $record->deliveryRate()) !== null ? " ({$rate}%)" : '')),
                TextEntry::make('failed_count')->label(__('sms.fields.failed'))->numeric()->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextEntry::make('respect_stop_list')->label(__('sms.fields.respect_stop_list'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('sms.yes') : __('sms.no')),
                TextEntry::make('error')->label(__('sms.fields.error'))->color('danger')->visible(fn (SmsCampaign $record): bool => filled($record->error))->columnSpanFull(),
            ]),
            Section::make(__('sms.campaigns.sections.text'))->columns(2)->schema([
                TextEntry::make('body')->label(__('sms.fields.body'))->helperText(fn (SmsCampaign $record): string => SmsFields::counter($record->body))
                    ->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html(),
                TextEntry::make('preview')->label(__('sms.campaigns.preview'))
                    ->state(fn (SmsCampaign $record, Campaigns $campaigns): string => $campaigns->textFor($record, $record->recipients[0] ?? ['phone' => '+995555000000', 'name' => null]))
                    ->helperText(fn (SmsCampaign $record, Campaigns $campaigns): string => SmsFields::counter($campaigns->textFor($record, $record->recipients[0] ?? ['phone' => '+995555000000', 'name' => null])))
                    ->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html(),
            ]),
            Section::make(__('sms.campaigns.sections.check'))->columns(['md' => 2, 'xl' => 4])->collapsible()->schema([
                TextEntry::make('audience')->label(__('sms.fields.audience'))->badge()->color('gray'),
                TextEntry::make('import_report.rows')->label(__('sms.report.rows'))->placeholder('0'),
                TextEntry::make('import_report.invalid_count')->label(__('sms.report.invalid'))->placeholder('0'),
                TextEntry::make('import_report.duplicates')->label(__('sms.report.duplicates'))->placeholder('0'),
                TextEntry::make('import_report.opted_out')->label(__('sms.report.opted_out'))->placeholder('0'),
                TextEntry::make('import_report.over_limit')->label(__('sms.report.over_limit'))->color('danger')
                    ->visible(fn (SmsCampaign $record): bool => (int) ($record->import_report['over_limit'] ?? 0) > 0),
                TextEntry::make('invalid_sample')->label(__('sms.report.invalid_sample'))
                    ->state(fn (SmsCampaign $record): array => $record->import_report['invalid'] ?? [])
                    ->badge()->color('danger')
                    ->visible(fn (SmsCampaign $record): bool => ($record->import_report['invalid'] ?? []) !== [])
                    ->columnSpanFull(),
                TextEntry::make('sample')->label(__('sms.report.sample'))
                    ->state(fn (SmsCampaign $record): array => array_map(
                        fn (array $recipient): string => GeorgianPhone::format($recipient['phone']).(filled($recipient['name'] ?? null) ? ' — '.$recipient['name'] : ''),
                        array_slice($record->recipients ?? [], 0, 10),
                    ) ?: ['—'])
                    ->listWithLineBreaks()
                    ->columnSpanFull(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('creator:id,name')->select(['id', 'name', 'status', 'audience', 'recipients_count', 'segments', 'import_report', 'sent_count', 'delivered_count', 'failed_count', 'scheduled_at', 'finished_at', 'created_by', 'created_at']))
            ->columns([
                TextColumn::make('name')->label(__('sms.fields.campaign_name'))->searchable()->weight('medium')->limit(50),
                TextColumn::make('status')->label(__('sms.fields.status'))->badge(),
                TextColumn::make('audience')->label(__('sms.fields.audience'))->badge()->color('gray')->toggleable(),
                TextColumn::make('recipients_count')->label(__('sms.fields.recipients'))->numeric()->sortable(),
                TextColumn::make('sent_count')->label(__('sms.fields.sent'))->numeric(),
                TextColumn::make('delivered_count')->label(__('sms.fields.delivered'))
                    ->formatStateUsing(fn (int $state, SmsCampaign $record): string => $state.(($rate = $record->deliveryRate()) !== null ? " ({$rate}%)" : '')),
                TextColumn::make('failed_count')->label(__('sms.fields.failed'))->numeric()->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('estimated')->label(__('sms.fields.estimated'))->state(fn (SmsCampaign $record): int => $record->estimatedSms())->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scheduled_at')->label(__('sms.fields.scheduled_at'))->dateTime('d.m.Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('finished_at')->label(__('sms.fields.finished_at'))->dateTime('d.m.Y H:i')->placeholder('—')->sortable()->toggleable(),
                TextColumn::make('creator.name')->label(__('sms.fields.created_by'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label(__('sms.fields.created_at'))->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('sms.fields.status'))->options(SmsCampaignStatus::class),
                SelectFilter::make('audience')->label(__('sms.fields.audience'))->options(SmsAudience::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (SmsCampaign $record): bool => $record->status->isEditable()),
                DeleteAction::make()->visible(fn (SmsCampaign $record): bool => in_array($record->status, [SmsCampaignStatus::Draft, SmsCampaignStatus::Cancelled], true)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\MessagesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsCampaigns::route('/'),
            'create' => Pages\CreateSmsCampaign::route('/create'),
            'view' => Pages\ViewSmsCampaign::route('/{record}'),
            'edit' => Pages\EditSmsCampaign::route('/{record}/edit'),
        ];
    }

    private static function audience(Get $get): ?SmsAudience
    {
        $value = $get('audience');

        return $value instanceof SmsAudience ? $value : SmsAudience::tryFrom((string) $value);
    }
}
