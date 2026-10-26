<?php

namespace App\Livewire\Listings;

use App\Enums\AmenityScope;
use App\Enums\HotelType;
use App\Enums\ListingModule;
use App\Models\Amenity;
use App\Models\Hotel;
use App\Services\PriceFormatter;
use App\Support\Listings\CardPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

/**
 * /hotels: dates and guests (only hotels with a suitable open room), price, type, stars,
 * distance to the sea, amenities, rating and location.
 *
 * @extends ListingIndex<Hotel>
 */
class HotelIndex extends ListingIndex
{
    public const array SEA_OPTIONS = [100, 300, 500, 1000];

    public const array RATING_OPTIONS = [7, 8, 9];

    #[Url(except: '')]
    public string $check_in = '';

    #[Url(except: '')]
    public string $check_out = '';

    #[Url(except: '')]
    public string $guests = '';

    #[Url(except: '')]
    public string $price_min = '';

    #[Url(except: '')]
    public string $price_max = '';

    /** @var list<string> */
    #[Url(except: [])]
    public $type = [];

    /** @var list<string> */
    #[Url(except: [])]
    public $stars = [];

    #[Url(except: '')]
    public string $sea = '';

    /** @var list<string> */
    #[Url(except: [])]
    public $amenities = [];

    #[Url(except: '')]
    public string $rating = '';

    #[Url(except: '')]
    public string $location = '';

    protected function model(): string
    {
        return Hotel::class;
    }

    protected function module(): ListingModule
    {
        return ListingModule::Hotels;
    }

    protected function relations(): array
    {
        return ['media', 'location', 'businessProfile.media'];
    }

    protected function viewName(): string
    {
        return 'livewire.listings.hotels';
    }

    protected function filterProperties(): array
    {
        return ['check_in', 'check_out', 'guests', 'price_min', 'price_max', 'type', 'stars', 'sea', 'amenities', 'rating', 'location'];
    }

    protected function sortOptions(): array
    {
        return [
            'recommended' => __('site.sort.recommended'),
            'price_asc' => __('site.sort.price_asc'),
            'price_desc' => __('site.sort.price_desc'),
            'sea' => __('site.sort.sea'),
            'rating' => __('site.sort.rating'),
            'newest' => __('site.sort.newest'),
        ];
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderByRaw('price_from asc nulls last'),
            'price_desc' => $query->orderByRaw('price_from desc nulls last'),
            'sea' => $query->orderByRaw('distance_to_sea_m asc nulls last'),
            'rating' => $query->orderByRaw('rating_avg desc nulls last')->orderByDesc('reviews_count'),
            'newest' => $query->latest('published_at'),
            default => $query->orderByPromotion(),
        };
    }

    protected function applyFilters(Builder $query): void
    {
        [$checkIn, $checkOut] = $this->stay();
        $guests = (int) $this->number($this->guests);

        if ($checkIn !== null || $guests > 0) {
            $suitable = function (Builder $rooms) use ($checkIn, $checkOut, $guests): void {
                if ($guests > 0) {
                    $rooms->whereRaw('capacity_adults + capacity_children >= ?', [$guests]);
                }

                if ($checkIn !== null && $checkOut !== null) {
                    // No closed night and no sold-out night in [check-in, check-out).
                    $rooms->whereDoesntHave('availability', fn (Builder $days) => $days
                        ->where('date', '>=', $checkIn->toDateString())
                        ->where('date', '<', $checkOut->toDateString())
                        ->where(fn (Builder $day) => $day->where('is_closed', true)->orWhere('units_available', 0)));
                }
            };

            // Hotels without room types publish no availability: kept for a dates-only search.
            $query->where(fn (Builder $hotels) => $hotels
                ->whereHas('roomTypes', $suitable)
                ->when($guests === 0, fn (Builder $hotels) => $hotels->orWhereDoesntHave('roomTypes')));
        }

        if ($checkIn !== null) {
            $month = $checkIn->month;
            $query->where(fn (Builder $season) => $season
                ->whereNull('season_start_month')
                ->orWhereNull('season_end_month')
                ->orWhere(fn (Builder $range) => $range->whereColumn('season_start_month', '<=', 'season_end_month')
                    ->where('season_start_month', '<=', $month)->where('season_end_month', '>=', $month))
                ->orWhere(fn (Builder $range) => $range->whereColumn('season_start_month', '>', 'season_end_month')
                    ->where(fn (Builder $wrap) => $wrap->where('season_start_month', '<=', $month)->orWhere('season_end_month', '>=', $month))));
        }

        if (($min = $this->number($this->price_min)) !== null) {
            $query->where('price_from', '>=', $min);
        }

        if (($max = $this->number($this->price_max)) !== null && $max > 0) {
            $query->where('price_from', '<=', $max);
        }

        $types = array_values(array_intersect($this->strings($this->type), array_column(HotelType::cases(), 'value')));

        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        $stars = array_values(array_filter(array_map('intval', $this->strings($this->stars)), fn (int $star): bool => $star >= 1 && $star <= 5));

        if ($stars !== []) {
            $query->whereIn('stars', $stars);
        }

        if (in_array((int) $this->sea, self::SEA_OPTIONS, true)) {
            $query->where('distance_to_sea_m', '<=', (int) $this->sea);
        }

        foreach (array_slice($this->strings($this->amenities), 0, 12) as $slug) {
            $query->whereHas('amenities', fn (Builder $amenity) => $amenity->where('slug', $slug));
        }

        if (in_array((int) $this->rating, self::RATING_OPTIONS, true)) {
            $query->where('rating_avg', '>=', (int) $this->rating);
        }

        if ($this->location !== '') {
            $query->whereIn('location_id', $this->locationIds($this->location));
        }
    }

    /**
     * Valid stay dates, or nulls. A missing check-out means one night.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function stay(): array
    {
        try {
            $checkIn = $this->check_in !== '' ? Carbon::createFromFormat('Y-m-d', $this->check_in)?->startOfDay() : null;
            $checkOut = $this->check_out !== '' ? Carbon::createFromFormat('Y-m-d', $this->check_out)?->startOfDay() : null;
        } catch (\Throwable) {
            return [null, null];
        }

        if ($checkIn === null || $checkIn->lt(today())) {
            return [null, null];
        }

        if ($checkOut === null || $checkOut->lte($checkIn)) {
            $checkOut = $checkIn->copy()->addDay();
        }

        return [$checkIn, $checkOut->diffInDays($checkIn, true) > 60 ? $checkIn->copy()->addDays(60) : $checkOut];
    }

    protected function card(Model $model): array
    {
        return app(CardPresenter::class)->hotel($model);
    }

    protected function filterOptions(): array
    {
        return [
            'typeOptions' => collect(HotelType::cases())->mapWithKeys(fn (HotelType $type): array => [$type->value => (string) $type->getLabel()])->all(),
            'amenityOptions' => Amenity::query()->forScope(AmenityScope::Hotel)->where('is_filterable', true)->orderBy('sort')->get()
                ->mapWithKeys(fn (Amenity $amenity): array => [$amenity->slug => (string) $amenity->name])->all(),
            'locationOptions' => $this->locationOptions(),
        ];
    }

    protected function chips(): array
    {
        $price = app(PriceFormatter::class);
        $chips = [];
        [$checkIn, $checkOut] = $this->stay();

        if ($checkIn !== null && $checkOut !== null) {
            $chips[] = ['label' => $checkIn->format('d.m').' – '.$checkOut->format('d.m'), 'property' => 'check_in'];
        }

        if ((int) $this->guests > 0) {
            $chips[] = ['label' => trans_choice('site.filters.guests_count', (int) $this->guests, ['count' => (int) $this->guests]), 'property' => 'guests'];
        }

        if ($this->price_min !== '' || $this->price_max !== '') {
            $chips[] = ['label' => $this->rangeLabel($price, $this->price_min, $this->price_max), 'property' => 'price_min'];
        }

        foreach ($this->strings($this->type) as $type) {
            $chips[] = ['label' => (string) (HotelType::tryFrom($type)?->getLabel() ?? $type), 'property' => 'type', 'value' => $type];
        }

        foreach ($this->strings($this->stars) as $star) {
            $chips[] = ['label' => trans_choice('site.filters.stars_count', (int) $star, ['count' => (int) $star]), 'property' => 'stars', 'value' => $star];
        }

        if ($this->sea !== '') {
            $chips[] = ['label' => __('site.filters.sea_up_to', ['distance' => $price->number((int) $this->sea)]), 'property' => 'sea'];
        }

        $amenities = $this->amenities !== [] ? $this->filterOptions()['amenityOptions'] : [];

        foreach ($this->strings($this->amenities) as $slug) {
            $chips[] = ['label' => $amenities[$slug] ?? $slug, 'property' => 'amenities', 'value' => $slug];
        }

        if ($this->rating !== '') {
            $chips[] = ['label' => __('site.filters.rating_from', ['rating' => $this->rating]), 'property' => 'rating'];
        }

        if ($this->location !== '') {
            $chips[] = ['label' => $this->locationLabel($this->location), 'property' => 'location'];
        }

        return $chips;
    }

    public function removeFilter(string $property, ?string $value = null): void
    {
        // Dates and the price range are one chip each.
        match ($property) {
            'check_in' => $this->reset('check_out'),
            'price_min' => $this->reset('price_max'),
            default => null,
        };

        parent::removeFilter($property, $value);
    }
}
