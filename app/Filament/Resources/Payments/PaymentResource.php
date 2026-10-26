<?php

namespace App\Filament\Resources\Payments;

use App\Actions\Billing\SyncPaymentStatus;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Exceptions\PaymentGatewayError;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Card payments with the full bank message log. Refunds are made on the order (Orders).
 */
class PaymentResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Payment::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 10;

    public static function requiredPermission(): Permission
    {
        return Permission::ManagePayments;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.payments.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.payments.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user.name')->label(__('admin.fields.payer')),
            TextEntry::make('amount')->formatStateUsing(fn ($state, Payment $record): string => number_format((float) $state, 2).' '.$record->currency->value),
            TextEntry::make('status')->badge(),
            TextEntry::make('gateway')->badge()->color('gray'),
            TextEntry::make('gateway_order_id')->placeholder('—'),
            TextEntry::make('paid_at')->dateTime('d.m.Y H:i')->placeholder('—'),
            TextEntry::make('refunded_amount')->placeholder('—'),
            TextEntry::make('refunded_at')->dateTime('d.m.Y H:i')->placeholder('—'),
            KeyValueEntry::make('payload')->getStateUsing(fn (Payment $record): array => collect($record->payload ?? [])->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))->all())->columnSpanFull(),
            RepeatableEntry::make('events')->label(__('admin.payments.log'))
                ->getStateUsing(fn (Payment $record) => $record->events()->limit(50)->get())
                ->schema([
                    TextEntry::make('created_at')->hiddenLabel()->dateTime('d.m.Y H:i:s'),
                    TextEntry::make('type')->hiddenLabel()->badge()->color('gray'),
                    TextEntry::make('status')->hiddenLabel()->placeholder('—'),
                    TextEntry::make('signature_valid')->hiddenLabel()->formatStateUsing(fn ($state): string => $state === null ? '—' : ($state ? __('admin.payments.signed') : __('admin.payments.bad_signature'))),
                    TextEntry::make('payload')->hiddenLabel()->formatStateUsing(fn ($state): string => mb_strimwidth((string) json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 400, '…'))->columnSpanFull(),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('user.name')->label(__('admin.fields.payer'))->searchable(),
                TextColumn::make('amount')->formatStateUsing(fn ($state, Payment $record): string => number_format((float) $state, 2).' '.$record->currency->value)->sortable(),
                TextColumn::make('gateway')->badge()->color('gray'),
                TextColumn::make('status')->badge(),
                TextColumn::make('paid_at')->dateTime('d.m.Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PaymentStatus::class),
                SelectFilter::make('gateway')->options(PaymentGateway::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('checkStatus')
                    ->label(__('admin.payments.check'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Pending && $record->gateway_order_id !== null)
                    ->action(function (Payment $record): void {
                        try {
                            $record = app(SyncPaymentStatus::class)->handle($record);
                        } catch (PaymentGatewayError $e) {
                            Notification::make()->danger()->title(__('admin.payments.check_failed'))->body($e->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title(__('admin.payments.checked', ['status' => $record->status->label()]))->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePayments::route('/')];
    }
}
