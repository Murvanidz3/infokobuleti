<?php

namespace App\Http\Controllers\Site;

use App\Enums\PropertyDeal;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ShowsListing;
use App\Models\Property;
use App\Services\Favorites;
use App\Services\Insights\PriceInsight;
use App\Services\PriceFormatter;
use App\Services\Seo\StructuredData;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\Contact;
use App\Support\Listings\DetailPresenter;
use App\Support\Listings\ListingQuery;
use Illuminate\View\View;

class PropertyController extends Controller
{
    use ShowsListing;

    public function show(Property $property, CardPresenter $cards, DetailPresenter $detail, PriceFormatter $price, Favorites $favorites, StructuredData $seo): View
    {
        $preview = $this->visit($property);
        $property->load(['media', 'location', 'businessProfile', 'user', 'features']);

        $similar = ListingQuery::public(Property::query())
            ->with(['media', 'location'])
            ->whereKeyNot($property->id)
            ->where('deal', $property->deal)
            ->orderByRaw('case when type = ? then 0 else 1 end', [$property->type->value])
            ->when($property->price_gel !== null, fn ($query) => $query->orderByRaw('abs(coalesce(price_gel, 0) - ?)', [(float) $property->price_gel]))
            ->limit(4)
            ->get()
            ->map(fn (Property $item): array => $cards->property($item));

        $currency = $cards->currency($property->currency);
        $facts = array_values(array_filter([
            ['icon' => 'tag', 'label' => __('home.search.fields.deal'), 'value' => (string) $property->deal->getLabel()],
            ['icon' => 'building', 'label' => __('site.filters.type'), 'value' => (string) $property->type->getLabel()],
            $property->area_m2 !== null ? ['icon' => 'area', 'label' => __('site.filters.area'), 'value' => __('listing.area', ['value' => $price->number((float) $property->area_m2)])] : null,
            $property->land_area_m2 !== null ? ['icon' => 'house', 'label' => __('site.real_estate.land_area'), 'value' => __('listing.area', ['value' => $price->number((float) $property->land_area_m2)])] : null,
            $property->rooms !== null ? ['icon' => 'door', 'label' => __('home.search.fields.rooms'), 'value' => (string) $property->rooms] : null,
            $property->bedrooms !== null ? ['icon' => 'bed', 'label' => __('site.real_estate.bedrooms'), 'value' => (string) $property->bedrooms] : null,
            $property->bathrooms !== null ? ['icon' => 'waves', 'label' => __('site.real_estate.bathrooms'), 'value' => (string) $property->bathrooms] : null,
            $property->floor !== null ? ['icon' => 'layers', 'label' => __('site.filters.floor'), 'value' => $property->total_floors ? $property->floor.' / '.$property->total_floors : (string) $property->floor] : null,
            $property->building_status !== null ? ['icon' => 'building', 'label' => __('site.filters.building'), 'value' => (string) $property->building_status->getLabel()] : null,
            $property->condition !== null ? ['icon' => 'wrench', 'label' => __('site.filters.condition'), 'value' => (string) $property->condition->getLabel()] : null,
            $property->distance_to_sea_m !== null ? ['icon' => 'waves', 'label' => __('site.hotels.to_sea'), 'value' => (string) $cards->sea($property->distance_to_sea_m)] : null,
            $property->price_per_m2 !== null && $property->deal === PropertyDeal::Sale ? ['icon' => 'tag', 'label' => __('site.real_estate.per_m2'), 'value' => $price->format((float) $property->price_per_m2, $currency)] : null,
            $property->cadastral_code ? ['icon' => 'file', 'label' => __('site.real_estate.cadastral'), 'value' => (string) $property->cadastral_code] : null,
        ]));

        return view('pages.real-estate.show', [
            'seo' => $seo->for($property),
            'property' => $property,
            'card' => $cards->property($property),
            'insight' => app(PriceInsight::class)->forProperty($property),
            'preview' => $preview,
            'gallery' => $detail->gallery($property),
            'facts' => $facts,
            'owner' => $detail->owner($property),
            'hasPhone' => Contact::phone($property) !== null,
            'nearby' => $detail->nearby($property),
            'similar' => $similar,
            'saved' => $favorites->isSaved($property),
            'description' => $detail->description($property),
        ]);
    }
}
