<?php

namespace App\Livewire\Billing;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PricePlanProduct;
use App\Livewire\Billing\Concerns\PaysOrders;
use App\Models\Order;
use App\Models\PricePlan;
use App\Models\Promotion;
use App\Models\WalletTransaction;
use App\Services\Billing\Catalog;
use App\Services\Billing\OrderBuilder;
use App\Services\DateFormatter;
use App\Services\PriceFormatter;
use App\Support\Listings\PublicUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The account billing page: wallet balance and top-ups, the business package, invoices issued by
 * staff, running promotions, and the order history with invoice downloads.
 */
class Billing extends Component
{
    use PaysOrders, WithPagination;

    public const int PER_PAGE = 10;

    public string $amount = '20';

    public int $package = 0;

    #[Locked]
    public string $path = '';

    public function mount(Catalog $catalog): void
    {
        $this->path = url()->current();
        $this->package = (int) ($catalog->businessPackages()->first()?->getKey() ?? 0);
    }

    public function topUp(OrderBuilder $orders): void
    {
        $min = (float) config('payments.top_up.min');
        $max = (float) config('payments.top_up.max');

        $this->validate(['amount' => ['required', 'numeric', 'min:'.$min, 'max:'.$max]], [], ['amount' => __('billing.wallet.amount')]);

        $this->pay(fn (): Order => $orders->topUp($this->user(), (float) $this->amount), PaymentMethod::Card);
    }

    public function buyPackage(OrderBuilder $orders): void
    {
        $plan = PricePlan::query()->whereKey($this->package)->where('product', PricePlanProduct::BusinessPackage->value)->first();

        if ($plan === null) {
            $this->dispatch('toast', message: __('billing.errors.choose_plan'), type: 'error');

            return;
        }

        $this->pay(fn (PaymentMethod $method): Order => $orders->forPackage($this->user(), $plan, $method));
    }

    public function payInvoice(int $order, string $with = 'card'): void
    {
        $invoice = Order::query()
            ->where('user_id', $this->user()->getKey())
            ->where('status', OrderStatus::Pending->value)
            ->whereNotNull('issued_by')
            ->find($order);

        if ($invoice === null) {
            $this->dispatch('toast', message: __('billing.errors.not_allowed'), type: 'error');

            return;
        }

        $this->pay(fn (): Order => $invoice, PaymentMethod::tryFrom($with) ?? PaymentMethod::Card);
    }

    #[On('billing-changed')]
    public function refreshAfterPurchase(): void
    {
        $this->user()->refresh();
    }

    public function render(Catalog $catalog, PriceFormatter $price, DateFormatter $dates): View
    {
        $user = $this->user();
        $balance = (float) $user->balance;
        $subscription = $user->activeBusinessSubscription();
        $packages = $catalog->businessPackages();
        $selectedPackage = $packages->firstWhere('id', $this->package);

        $orders = Order::query()
            ->where('user_id', $user->getKey())
            ->where('status', '!=', OrderStatus::Pending->value)
            ->with(['pricePlan', 'purchasable'])
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withPath($this->path);

        return view('livewire.billing.billing', [
            'balance' => $price->format($balance),
            'presets' => collect((array) config('payments.top_up.presets'))->mapWithKeys(fn ($value): array => [(string) $value => $price->format((float) $value)])->all(),
            'limits' => ['min' => config('payments.top_up.min'), 'max' => config('payments.top_up.max')],
            'gateways' => $this->gatewayOptions(),
            'subscription' => $subscription === null ? null : [
                'until' => $dates->date($subscription->ends_at),
                'limit' => $subscription->listings_limit,
            ],
            'packages' => $packages->map(fn (PricePlan $plan): array => [
                'id' => (int) $plan->getKey(),
                'name' => (string) $plan->name,
                'price' => $price->format((float) $plan->price, $plan->currency->value),
                'days' => trans_choice('billing.days', (int) ($plan->duration_days ?? 30), ['count' => $plan->duration_days ?? 30]),
                'limit' => $plan->listings_limit,
            ])->all(),
            'enoughBalance' => $selectedPackage !== null && $balance >= (float) $selectedPackage->price,
            'invoices' => Order::query()->where('user_id', $user->getKey())->where('status', OrderStatus::Pending->value)->whereNotNull('issued_by')->latest('id')->get()
                ->map(fn (Order $order): array => [
                    'id' => (int) $order->getKey(),
                    'title' => $order->title(),
                    'amount' => $price->format((float) $order->amount, $order->currency->value),
                    'payable' => $balance >= (float) $order->amount,
                    'date' => $dates->date($order->created_at ?? now()),
                ])->all(),
            'promotions' => $this->promotions($dates),
            'orders' => $orders,
            'rows' => collect($orders->items())->map(fn (Order $order): array => [
                'id' => (int) $order->getKey(),
                'number' => $order->number,
                'title' => $order->title(),
                'subject' => $order->purchasable !== null ? (string) $order->purchasable->getAttribute('title') : null,
                'amount' => $price->format((float) $order->amount, $order->currency->value),
                'refunded' => (float) $order->refunded_amount > 0 ? $price->format((float) $order->refunded_amount, $order->currency->value) : null,
                'status' => $order->status->label(),
                'tone' => $order->status->getColor(),
                'method' => $order->method?->label(),
                'date' => $dates->date($order->paid_at ?? $order->created_at ?? now()),
                'invoice' => $order->number !== null ? localized_route('account.invoice', ['order' => $order->getKey()]) : null,
            ])->all(),
            'ledger' => WalletTransaction::query()->where('user_id', $user->getKey())->latest('id')->limit(8)->get()
                ->map(fn (WalletTransaction $entry): array => [
                    'type' => $entry->type->label(),
                    'note' => $entry->note !== null ? (__('billing.wallet.notes.'.$entry->note) !== 'billing.wallet.notes.'.$entry->note ? __('billing.wallet.notes.'.$entry->note) : $entry->note) : null,
                    'amount' => ((float) $entry->amount > 0 ? '+' : '−').$price->format(abs((float) $entry->amount)),
                    'credit' => (float) $entry->amount > 0,
                    'date' => $dates->date($entry->created_at ?? now()),
                ])->all(),
        ]);
    }

    /**
     * Running promotions on the user's listings, newest end first.
     *
     * @return list<array{label: string, title: string, until: string, url: ?string, promote: string}>
     */
    private function promotions(DateFormatter $dates): array
    {
        return Promotion::query()
            ->where('user_id', $this->user()->getKey())
            ->where('type', '!=', 'bump')
            ->active()
            ->with('promotable')
            ->orderByDesc('ends_at')
            ->limit(20)
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->promotable !== null)
            ->map(function (Promotion $promotion) use ($dates): array {
                /** @var Model $listing */
                $listing = $promotion->promotable;

                return [
                    'label' => $promotion->type->label(),
                    'title' => (string) $listing->getAttribute('title'),
                    'until' => $promotion->ends_at !== null ? __('billing.until', ['date' => $dates->date($promotion->ends_at)]) : '',
                    'url' => method_exists($listing, 'isPublished') && $listing->isPublished() ? (string) PublicUrl::for($listing) : null,
                    'promote' => localized_route('account.promote', ['type' => $listing->getMorphClass(), 'id' => $listing->getKey()]),
                ];
            })
            ->values()
            ->all();
    }
}
