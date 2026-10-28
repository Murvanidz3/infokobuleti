<?php

namespace App\Filament\Resources\Orders;

use App\Actions\Billing\RefundOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Enums\PricePlanProduct;
use App\Exceptions\PaymentGatewayError;
use App\Exceptions\PurchaseNotAllowed;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Support\ChecksPermission;
use App\Models\Order;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Orders: everything bought on the site (promotions, packages, top-ups, staff invoices), with
 * refunds and staff-issued invoices.
 */
class OrderResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Order::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 8;

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
        return __('admin.resources.orders.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.orders.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('number')->label(__('admin.fields.invoice_number'))->placeholder('—'),
            TextEntry::make('user.name')->label(__('admin.fields.payer'))->placeholder('—'),
            TextEntry::make('product')->badge()->color('gray'),
            TextEntry::make('status')->badge(),
            TextEntry::make('title')->label(__('admin.fields.title'))->getStateUsing(fn (Order $record): string => $record->title()),
            TextEntry::make('purchasable_type')->label(__('admin.fields.subject'))
                ->getStateUsing(fn (Order $record): string => $record->purchasable_type ? __('admin.morph.'.$record->purchasable_type).': '.ReviewResource::subjectTitle($record->purchasable) : '—'),
            TextEntry::make('amount')->formatStateUsing(fn ($state, Order $record): string => self::money((float) $state, $record)),
            TextEntry::make('refunded_amount')->label(__('admin.fields.refunded_amount'))->formatStateUsing(fn ($state, Order $record): string => self::money((float) $state, $record)),
            TextEntry::make('method')->badge()->color('gray')->placeholder('—'),
            TextEntry::make('issuer.name')->label(__('admin.fields.issued_by'))->placeholder('—'),
            TextEntry::make('created_at')->dateTime('d.m.Y H:i'),
            TextEntry::make('paid_at')->dateTime('d.m.Y H:i')->placeholder('—'),
            TextEntry::make('fulfilled_at')->label(__('admin.fields.fulfilled_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
            TextEntry::make('refunded_at')->dateTime('d.m.Y H:i')->placeholder('—'),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'pricePlan', 'purchasable', 'issuer']))
            ->columns([
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('number')->label(__('admin.fields.invoice_number'))->searchable()->placeholder('—'),
                TextColumn::make('user.name')->label(__('admin.fields.payer'))->searchable()->placeholder('—'),
                TextColumn::make('product')->badge()->color('gray'),
                TextColumn::make('title')->label(__('admin.fields.title'))->getStateUsing(fn (Order $record): string => $record->title())->limit(40)->toggleable(),
                TextColumn::make('amount')->formatStateUsing(fn ($state, Order $record): string => self::money((float) $state, $record))->sortable(),
                TextColumn::make('method')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('paid_at')->dateTime('d.m.Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class),
                SelectFilter::make('product')->options(PricePlanProduct::class),
                SelectFilter::make('method')->options(PaymentMethod::class),
                Filter::make('paid_between')
                    ->schema([
                        DatePicker::make('from')->label(__('admin.revenue.from')),
                        DatePicker::make('until')->label(__('admin.revenue.to')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('paid_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('paid_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                self::refundAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function refundAction(): Action
    {
        return Action::make('refund')
            ->label(__('admin.orders.refund'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->visible(fn (Order $record): bool => $record->status->isSettled() && $record->refundableAmount() > 0)
            ->modalDescription(fn (Order $record): string => __($record->method === PaymentMethod::Card ? 'admin.orders.refund_card_help' : 'admin.orders.refund_balance_help'))
            ->fillForm(fn (Order $record): array => ['amount' => $record->refundableAmount()])
            ->schema([
                TextInput::make('amount')->label(__('admin.fields.amount'))->numeric()->minValue(0.01)
                    ->maxValue(fn (Order $record): float => $record->refundableAmount())->required(),
                TextInput::make('reason')->label(__('admin.fields.reason'))->maxLength(200)->required(),
            ])
            ->requiresConfirmation()
            ->action(function (Order $record, array $data): void {
                /** @var User $staff */
                $staff = auth()->user();

                try {
                    app(RefundOrder::class)->handle($record, (float) $data['amount'], $staff, $data['reason']);
                } catch (PurchaseNotAllowed) {
                    Notification::make()->danger()->title(__('admin.orders.refund_not_allowed'))->send();

                    return;
                } catch (PaymentGatewayError $e) {
                    Notification::make()->danger()->title(__('admin.orders.refund_failed'))->body($e->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title(__('admin.orders.refunded'))->send();
            });
    }

    private static function money(float $amount, Order $order): string
    {
        return number_format($amount, 2, '.', ' ').' '.$order->currency->value;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageOrders::route('/')];
    }
}
