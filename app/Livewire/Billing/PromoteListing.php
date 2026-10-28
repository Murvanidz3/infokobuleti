<?php

namespace App\Livewire\Billing;

use App\Contracts\Listing;
use App\Enums\PaymentMethod;
use App\Enums\PricePlanProduct;
use App\Livewire\Billing\Concerns\PaysOrders;
use App\Models\Order;
use App\Models\PricePlan;
use App\Models\Promotion;
use App\Services\Account\OwnerListings;
use App\Services\Billing\Catalog;
use App\Services\Billing\OrderBuilder;
use App\Services\DateFormatter;
use App\Services\PriceFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Promote one listing: VIP, Super VIP, homepage slot (hotels), bump or auto-bump, paid from the
 * balance or by card.
 */
class PromoteListing extends Component
{
    use PaysOrders;

    /** Order the products are offered in. */
    private const array PRODUCTS = [
        PricePlanProduct::SuperVip,
        PricePlanProduct::Vip,
        PricePlanProduct::FeaturedHotel,
        PricePlanProduct::Bump,
        PricePlanProduct::AutoBump,
    ];

    #[Locked]
    public string $type = '';

    #[Locked]
    public int $id = 0;

    public int $plan = 0;

    public function mount(Catalog $catalog, OwnerListings $listings): void
    {
        $plans = $catalog->forListing($this->listing($listings));
        $this->plan = (int) ($plans->firstWhere('product', PricePlanProduct::Vip)?->getKey() ?? $plans->first()?->getKey() ?? 0);
    }

    public function choose(int $plan): void
    {
        $this->plan = $plan;
    }

    public function buy(OwnerListings $listings, OrderBuilder $orders): void
    {
        $listing = $this->listing($listings);
        $plan = PricePlan::query()->find($this->plan);

        if ($plan === null) {
            $this->dispatch('toast', message: __('billing.errors.choose_plan'), type: 'error');

            return;
        }

        $this->pay(fn (PaymentMethod $method): Order => $orders->forListing($this->user(), $plan, $listing, $method));
    }

    #[On('billing-changed')]
    public function refreshAfterPurchase(): void
    {
        // Re-render with the new promotion and balance.
    }

    public function render(Catalog $catalog, OwnerListings $listings, PriceFormatter $price, DateFormatter $dates): View
    {
        $listing = $this->listing($listings);
        $plans = $catalog->forListing($listing);
        $selected = $plans->firstWhere('id', $this->plan);
        $balance = (float) $this->user()->balance;

        $groups = collect(self::PRODUCTS)
            ->map(fn (PricePlanProduct $product): array => [
                'product' => $product->value,
                'label' => $product->label(),
                'text' => __('billing.products.'.$product->value),
                'plans' => $plans->where('product', $product)->values()->map(fn (PricePlan $plan): array => [
                    'id' => (int) $plan->getKey(),
                    'name' => (string) $plan->name,
                    'days' => $plan->duration_days !== null ? trans_choice('billing.days', (int) $plan->duration_days, ['count' => $plan->duration_days]) : __('billing.once'),
                    'price' => $price->format((float) $plan->price, $plan->currency->value),
                ])->all(),
            ])
            ->filter(fn (array $group): bool => $group['plans'] !== [])
            ->values()
            ->all();

        return view('livewire.billing.promote-listing', [
            'row' => $listings->rows([$listing])[0],
            'canPromote' => $catalog->canPromote($listing),
            'groups' => $groups,
            'active' => $this->activePromotions($listing, $dates),
            'selected' => $selected,
            'selectedPrice' => $selected !== null ? $price->format((float) $selected->price, $selected->currency->value) : null,
            'balance' => $price->format($balance),
            'enoughBalance' => $selected !== null && $balance >= (float) $selected->price,
            'gateways' => $this->gatewayOptions(),
        ]);
    }

    /**
     * @return list<array{label: string, until: string, icon: string}>
     */
    private function activePromotions(Model $listing, DateFormatter $dates): array
    {
        return Promotion::query()
            ->where('promotable_type', $listing->getMorphClass())
            ->where('promotable_id', $listing->getKey())
            ->where('type', '!=', 'bump')
            ->active()
            ->orderByDesc('ends_at')
            ->get()
            ->unique(fn (Promotion $promotion): string => $promotion->type->value)
            ->map(fn (Promotion $promotion): array => [
                'label' => $promotion->type->label(),
                'until' => $promotion->ends_at !== null ? __('billing.until', ['date' => $dates->date($promotion->ends_at)]) : '',
                'icon' => $promotion->type->value === 'auto_bump' ? 'refresh' : 'star',
            ])
            ->values()
            ->all();
    }

    private function listing(OwnerListings $listings): Model&Listing
    {
        return $listings->find($this->user(), $this->type, $this->id);
    }
}
