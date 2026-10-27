<?php

namespace App\Filament\Resources\Users;

use App\Enums\AccountType;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\WalletEntryType;
use App\Exceptions\InsufficientBalance;
use App\Filament\NavigationGroup;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\SmsFields;
use App\Models\User;
use App\Services\Auth\Impersonation;
use App\Services\Billing\Wallet;
use App\Support\GeorgianPhone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as RoleModel;
use UnitEnum;

class UserResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = User::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Users;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function requiredPermission(): Permission
    {
        return Permission::ManageUsers;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.users.plural');
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    /**
     * Only super admins hand out staff roles.
     *
     * @return array<string, string>
     */
    public static function assignableRoles(): array
    {
        $canGrantStaff = auth()->user()?->hasRole(Role::SuperAdmin->value) ?? false;

        return collect(Role::cases())
            ->filter(fn (Role $role): bool => $canGrantStaff || ! $role->isStaff())
            ->mapWithKeys(fn (Role $role): array => [$role->value => $role->label()])
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.profile'))
                ->schema([
                    TextInput::make('name')->maxLength(80),
                    TextInput::make('email')->email()->unique(ignoreRecord: true)->requiredWithout('phone'),
                    TextInput::make('phone')
                        ->tel()
                        ->placeholder('+995 5XX XX XX XX')
                        ->dehydrateStateUsing(fn (?string $state): ?string => GeorgianPhone::normalize($state) ?? ($state ?: null))
                        ->rule(fn () => fn (string $attribute, $value, $fail) => filled($value) && ! GeorgianPhone::isValid($value) ? $fail(__('auth.phone.invalid')) : null)
                        ->unique(ignoreRecord: true)
                        ->requiredWithout('email'),
                    Select::make('account_type')->options(AccountType::class)->default(AccountType::Individual->value)->required(),
                    Select::make('locale')->options(['ka' => 'KA', 'en' => 'EN', 'ru' => 'RU'])->default('ka')->required(),
                ])
                ->columns(3),
            Section::make(__('admin.sections.access'))
                ->schema([
                    CheckboxList::make('roles')
                        ->relationship('roles', 'name', fn (Builder $query) => $query->whereIn('name', array_keys(self::assignableRoles())))
                        ->getOptionLabelFromRecordUsing(fn (RoleModel $role): string => Role::from($role->name)->label())
                        ->columns(3)
                        ->columnSpanFull(),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->helperText(__('admin.users.password_help'))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                        ->minLength(12)
                        ->visible(fn (): bool => auth()->user()?->hasRole(Role::SuperAdmin->value) ?? false),
                ])
                ->columns(2),
        ])->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextEntry::make('name')->placeholder('—'),
                    TextEntry::make('phone')->formatStateUsing(fn (?string $state): string => GeorgianPhone::format($state))->placeholder('—'),
                    TextEntry::make('email')->placeholder('—'),
                    TextEntry::make('account_type')->badge(),
                    TextEntry::make('roles.name')->badge()->formatStateUsing(fn (string $state): string => Role::tryFrom($state)?->label() ?? $state),
                    TextEntry::make('locale')->badge()->color('gray'),
                    IconEntry::make('phone_verified_at')->label(__('admin.fields.phone_verified'))->boolean()->getStateUsing(fn (User $record): bool => $record->hasVerifiedPhone()),
                    IconEntry::make('google_id')->label(__('admin.fields.google'))->boolean()->getStateUsing(fn (User $record): bool => $record->hasGoogle()),
                    IconEntry::make('two_factor_confirmed_at')->label(__('admin.fields.two_factor'))->boolean()->getStateUsing(fn (User $record): bool => $record->hasTwoFactorEnabled()),
                    TextEntry::make('balance')->label(__('admin.fields.balance'))->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ' ').' ₾'),
                    TextEntry::make('created_at')->dateTime('d.m.Y H:i'),
                    TextEntry::make('last_seen_at')->dateTime('d.m.Y H:i')->placeholder('—'),
                    IconEntry::make('is_blocked')->boolean(),
                    TextEntry::make('blocked_reason')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(4),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('roles'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->placeholder('—'),
                TextColumn::make('phone')->formatStateUsing(fn (?string $state): string => GeorgianPhone::format($state))->searchable()->placeholder('—'),
                TextColumn::make('email')->searchable()->toggleable()->placeholder('—'),
                TextColumn::make('roles.name')->badge()->formatStateUsing(fn (string $state): string => Role::tryFrom($state)?->label() ?? $state),
                TextColumn::make('account_type')->badge()->color('gray')->toggleable(),
                IconColumn::make('is_blocked')->boolean()->trueColor('danger')->falseIcon(null),
                TextColumn::make('last_seen_at')->dateTime('d.m.Y H:i')->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->relationship('roles', 'name')->getOptionLabelFromRecordUsing(fn (RoleModel $role): string => Role::from($role->name)->label())->multiple()->preload(),
                SelectFilter::make('account_type')->options(AccountType::class),
                TernaryFilter::make('is_blocked'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                self::blockAction(),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::unblockAction(),
                    self::impersonateAction(),
                    self::resetTwoFactorAction(),
                    self::adjustBalanceAction(),
                    SmsFields::sendAction(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Support credit or correction of the wallet balance (positive adds, negative takes away).
     */
    public static function adjustBalanceAction(): Action
    {
        return Action::make('adjustBalance')
            ->label(__('admin.users.adjust_balance'))
            ->icon(Heroicon::OutlinedBanknotes)
            ->visible(fn (): bool => auth()->user()?->can(Permission::ManagePayments->value) ?? false)
            ->schema([
                TextInput::make('amount')->label(__('admin.fields.amount'))->numeric()->required()->rule('not_in:0')->helperText(__('admin.users.adjust_balance_help')),
                TextInput::make('note')->label(__('admin.fields.note'))->required()->maxLength(200),
            ])
            ->action(function (User $record, array $data): void {
                $amount = round((float) $data['amount'], 2);
                /** @var User $staff */
                $staff = auth()->user();

                try {
                    $amount > 0
                        ? app(Wallet::class)->credit($record, $amount, WalletEntryType::Adjustment, note: $data['note'], author: $staff)
                        : app(Wallet::class)->debit($record, $amount, WalletEntryType::Adjustment, note: $data['note'], author: $staff);
                } catch (InsufficientBalance) {
                    Notification::make()->danger()->title(__('admin.users.balance_too_low'))->send();

                    return;
                }

                activity('billing')->performedOn($record)->causedBy($staff)->event('balance_adjusted')->withProperties(['amount' => $amount, 'note' => $data['note']])->log('balance adjusted');

                Notification::make()->success()->title(__('admin.saved'))->send();
            });
    }

    public static function blockAction(): Action
    {
        return Action::make('block')
            ->label(__('admin.users.block'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => ! $record->is_blocked && $record->getKey() !== auth()->id() && (! $record->isStaff() || auth()->user()?->hasRole(Role::SuperAdmin->value)))
            ->schema([Textarea::make('reason')->label(__('admin.users.block_reason'))->required()->rows(3)])
            ->action(function (User $record, array $data): void {
                $record->forceFill(['is_blocked' => true, 'blocked_reason' => $data['reason']])->save();
                activity('admin')->performedOn($record)->causedBy(auth()->user())->event('blocked')->withProperties(['reason' => $data['reason']])->log('user blocked');

                Notification::make()->success()->title(__('admin.users.blocked'))->send();
            });
    }

    public static function unblockAction(): Action
    {
        return Action::make('unblock')
            ->label(__('admin.users.unblock'))
            ->icon(Heroicon::OutlinedLockOpen)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => $record->is_blocked)
            ->action(function (User $record): void {
                $record->forceFill(['is_blocked' => false, 'blocked_reason' => null])->save();
                activity('admin')->performedOn($record)->causedBy(auth()->user())->event('unblocked')->log('user unblocked');

                Notification::make()->success()->title(__('admin.users.unblocked'))->send();
            });
    }

    public static function impersonateAction(): Action
    {
        return Action::make('impersonate')
            ->label(__('admin.users.impersonate'))
            ->icon(Heroicon::OutlinedEye)
            ->visible(fn (User $record): bool => auth()->user() !== null && Impersonation::canImpersonate(auth()->user(), $record))
            ->url(fn (User $record): string => route('impersonation.start', $record))
            ->postToUrl();
    }

    public static function resetTwoFactorAction(): Action
    {
        return Action::make('resetTwoFactor')
            ->label(__('admin.users.reset_two_factor'))
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('admin.users.reset_two_factor_help'))
            ->visible(fn (User $record): bool => $record->hasTwoFactorEnabled() && $record->getKey() !== auth()->id() && (auth()->user()?->hasRole(Role::SuperAdmin->value) ?? false))
            ->action(function (User $record): void {
                $record->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
                activity('admin')->performedOn($record)->causedBy(auth()->user())->event('two_factor_reset')->log('two-factor reset');

                Notification::make()->success()->title(__('admin.saved'))->send();
            });
    }

    /**
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [ActivitiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
