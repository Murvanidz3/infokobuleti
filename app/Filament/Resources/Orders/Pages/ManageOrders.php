<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\PricePlanProduct;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Banner;
use App\Models\PricePlan;
use App\Models\User;
use App\Notifications\InvoiceIssued;
use App\Services\Billing\OrderBuilder;
use App\Support\GeorgianPhone;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issueInvoice')
                ->label(__('admin.orders.issue_invoice'))
                ->icon(Heroicon::OutlinedDocumentPlus)
                ->modalDescription(__('admin.orders.issue_invoice_help'))
                ->schema([
                    Select::make('user_id')->label(__('admin.fields.payer'))->required()->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => User::query()
                            ->where(fn ($query) => $query->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%")->orWhere('phone', 'like', '%'.preg_replace('/\D/', '', $search).'%'))
                            ->limit(20)->get()
                            ->mapWithKeys(fn (User $user): array => [$user->id => trim(($user->name ?? '').' '.GeorgianPhone::format($user->phone).' '.($user->email ?? ''))])
                            ->all())
                        ->getOptionLabelUsing(fn ($value): ?string => User::query()->find($value)?->name),
                    Select::make('price_plan_id')->label(__('admin.fields.price_plan'))
                        ->options(fn (): array => PricePlan::query()->whereIn('product', [PricePlanProduct::Banner->value, PricePlanProduct::BusinessPackage->value])->orderBy('sort')->get()->mapWithKeys(fn (PricePlan $plan): array => [$plan->id => (string) $plan->name])->all())
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('amount', PricePlan::query()->find($state)?->price)),
                    Select::make('banner_id')->label(__('admin.resources.banners.singular'))
                        ->options(fn (): array => Banner::query()->latest('id')->limit(100)->get()->mapWithKeys(fn (Banner $banner): array => [$banner->id => (string) $banner->title])->all())
                        ->helperText(__('admin.orders.banner_help')),
                    TextInput::make('amount')->label(__('admin.fields.amount'))->numeric()->minValue(1)->required(),
                    Grid::make(3)->schema([
                        TextInput::make('description.ka')->label(__('admin.fields.title').' (KA)')->required()->maxLength(120),
                        TextInput::make('description.en')->label(__('admin.fields.title').' (EN)')->maxLength(120),
                        TextInput::make('description.ru')->label(__('admin.fields.title').' (RU)')->maxLength(120),
                    ]),
                ])
                ->action(function (array $data, OrderBuilder $orders): void {
                    /** @var User $staff */
                    $staff = auth()->user();
                    $user = User::query()->findOrFail($data['user_id']);
                    $banner = filled($data['banner_id'] ?? null) ? Banner::query()->find($data['banner_id']) : null;
                    $plan = filled($data['price_plan_id'] ?? null) ? PricePlan::query()->find($data['price_plan_id']) : null;

                    // A banner invoice without a banner plan is still a banner purchase.
                    $order = $orders->invoice($user, (float) $data['amount'], (array) $data['description'], $staff, $plan, $banner);

                    if ($banner !== null && $plan === null) {
                        $order->forceFill(['product' => PricePlanProduct::Custom])->save();
                    }

                    $user->notify(new InvoiceIssued($order));
                    activity('billing')->performedOn($order)->causedBy($staff)->event('invoice_issued')->log('invoice issued');

                    Notification::make()->success()->title(__('admin.orders.invoice_issued'))->send();
                }),
        ];
    }
}
