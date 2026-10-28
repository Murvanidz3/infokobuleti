<?php

namespace App\Services\Billing;

use App\Contracts\Listing;
use App\Enums\ListingModule;
use App\Enums\ListingStatus;
use App\Enums\PricePlanProduct;
use App\Models\Hotel;
use App\Models\PricePlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Which price plans can be bought, and for what. Prices and availability come from the admin.
 */
class Catalog
{
    /** Listing morph alias => module the price plans are scoped to. */
    public const array MODULES = [
        'hotel' => ListingModule::Hotels,
        'property' => ListingModule::Properties,
        'classified' => ListingModule::Classifieds,
        'vacancy' => ListingModule::Vacancies,
    ];

    /**
     * Active promotion plans for one listing, cheapest first within each product.
     *
     * @return Collection<int, PricePlan>
     */
    public function forListing(Model&Listing $listing): Collection
    {
        $module = self::MODULES[$listing->getMorphClass()] ?? null;

        return PricePlan::query()
            ->where('is_active', true)
            ->whereIn('product', $this->listingProducts($listing))
            ->where(fn ($query) => $query->whereNull('module')->when($module, fn ($query) => $query->orWhere('module', $module->value)))
            ->orderBy('sort')
            ->orderBy('price')
            ->get();
    }

    /**
     * @return Collection<int, PricePlan>
     */
    public function businessPackages(): Collection
    {
        return PricePlan::query()
            ->where('is_active', true)
            ->where('product', PricePlanProduct::BusinessPackage->value)
            ->orderBy('sort')
            ->get();
    }

    public function allowsForListing(PricePlan $plan, Model&Listing $listing): bool
    {
        return $this->forListing($listing)->contains(fn (PricePlan $candidate): bool => $candidate->is($plan));
    }

    /**
     * Only live listings can be promoted.
     */
    public function canPromote(Model&Listing $listing): bool
    {
        $expiresAt = $listing->getAttribute('expires_at');

        return $listing->listingStatus() === ListingStatus::Published
            && ($expiresAt === null || $expiresAt->isFuture());
    }

    /**
     * @return list<string>
     */
    private function listingProducts(Model $listing): array
    {
        $products = [PricePlanProduct::Vip, PricePlanProduct::SuperVip, PricePlanProduct::Bump, PricePlanProduct::AutoBump];

        // Homepage slots are sold for hotels.
        if ($listing instanceof Hotel) {
            $products[] = PricePlanProduct::FeaturedHotel;
        }

        return array_map(fn (PricePlanProduct $product): string => $product->value, $products);
    }
}
