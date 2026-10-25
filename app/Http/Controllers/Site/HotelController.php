<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ShowsListing;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\Favorites;
use App\Services\PriceFormatter;
use App\Support\Listings\AvailabilityCalendar;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\Contact;
use App\Support\Listings\DetailPresenter;
use App\Support\Listings\ListingQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelController extends Controller
{
    use ShowsListing;

    public function show(Request $request, Hotel $hotel, CardPresenter $cards, DetailPresenter $detail, AvailabilityCalendar $calendar, PriceFormatter $price, Favorites $favorites): View
    {
        $preview = $this->visit($hotel);
        $hotel->load(['media', 'location', 'businessProfile', 'user', 'amenities', 'roomTypes.media', 'roomTypes.amenities']);

        $similar = ListingQuery::public(Hotel::query())
            ->with(['media', 'location'])
            ->whereKeyNot($hotel->id)
            ->orderByRaw('case when type = ? then 0 else 1 end', [$hotel->type->value])
            ->orderByPromotion()
            ->limit(4)
            ->get()
            ->map(fn (Hotel $item): array => $cards->hotel($item));

        $facts = array_values(array_filter([
            ['icon' => 'bed', 'label' => __('site.filters.type'), 'value' => (string) $hotel->type->getLabel()],
            $hotel->stars ? ['icon' => 'star', 'label' => __('site.filters.stars'), 'value' => str_repeat('★', (int) $hotel->stars)] : null,
            $hotel->distance_to_sea_m !== null ? ['icon' => 'waves', 'label' => __('site.hotels.to_sea'), 'value' => (string) $cards->sea($hotel->distance_to_sea_m)] : null,
            $hotel->check_in_from ? ['icon' => 'clock', 'label' => __('site.hotels.check_in'), 'value' => __('site.hotels.from_time', ['time' => substr((string) $hotel->check_in_from, 0, 5)])] : null,
            $hotel->check_out_until ? ['icon' => 'clock', 'label' => __('site.hotels.check_out'), 'value' => __('site.hotels.until_time', ['time' => substr((string) $hotel->check_out_until, 0, 5)])] : null,
            $hotel->rooms_count ? ['icon' => 'door', 'label' => __('site.hotels.rooms'), 'value' => (string) $hotel->rooms_count] : null,
            $hotel->season_start_month && $hotel->season_end_month ? ['icon' => 'sun', 'label' => __('site.hotels.season'), 'value' => __('time.months.'.$hotel->season_start_month).' – '.__('time.months.'.$hotel->season_end_month)] : null,
        ]));

        return view('pages.hotels.show', [
            'hotel' => $hotel,
            'preview' => $preview,
            'gallery' => $detail->gallery($hotel),
            'facts' => $facts,
            'owner' => $detail->owner($hotel),
            'hasPhone' => Contact::phone($hotel) !== null,
            'nearby' => $detail->nearby($hotel),
            'reviews' => $detail->reviews($hotel),
            'rooms' => $hotel->roomTypes->map(fn (RoomType $room): array => [
                'id' => $room->id,
                'name' => (string) $room->name,
                'description' => $cards->excerpt($room->description, 220),
                'capacity' => (int) $room->capacity_adults + (int) $room->capacity_children,
                'size' => $room->size_m2,
                'price' => $price->format((float) $room->base_price),
                'image' => $room->coverUrl('thumb'),
                'amenities' => $room->amenities->take(6)->map(fn ($amenity): string => (string) $amenity->name)->all(),
            ])->all(),
            'months' => $calendar->months($hotel, 3),
            'similar' => $similar,
            'saved' => $favorites->isSaved($hotel),
            'address' => $cards->locationText($hotel),
            'priceFrom' => $hotel->price_from !== null ? $price->format((float) $hotel->price_from) : null,
            'stay' => [
                'check_in' => (string) $request->query('check_in', ''),
                'check_out' => (string) $request->query('check_out', ''),
                'guests' => max(1, min(20, (int) $request->query('guests', 2))),
            ],
            'description' => $detail->description($hotel),
        ]);
    }
}
