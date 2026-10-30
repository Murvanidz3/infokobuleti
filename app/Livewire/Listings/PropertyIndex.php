<?php

namespace App\Livewire\Listings;

use App\Contracts\ExchangeRates;
use App\Enums\AmenityScope;
use App\Enums\BuildingStatus;
use App\Enums\Currency;
use App\Enums\ListingModule;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\Amenity;
use App\Models\Property;
use App\Services\PriceFormatter;
use App\Support\Listings\CardPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * /real-estate: deal, type, district, price in GEL or USD, area, rooms, floor, condition,
 * building status, distance to the sea, amenities, keyword and "with photos only".
 *
 * @extends ListingIndex<Property>
 */
class PropertyIndex extends ListingIndex
{
    public const array ROOM_OPTIONS = ['1', '2', '3', '4'];

    public const array SEA_OPTIONS = [100, 300, 500, 1000];

    #[Url(except: '')]
    public string $deal = '';

    /** @var list<string> */
    #[Url(except: [])]
    public $type = [];

    #[Url(except: 'GEL')]
    public string $currency = 'GEL';

    #[Url(except: '')]
    public string $price_min = '';

    #[Url(except: '')]
    public string $price_max = '';

    #[Url(except: '')]
    public string $area_min = '';

    #[Url(except: '')]
    public string $area_max = '';

    /** @var list<string> "4" means four or more */
    #[Url(except: [])]
    public $rooms = [];

    #[Url(except: '')]
    public string $floor_min = '';

    #[Url(except: '')]
    public string $floor_max = '';

    /** @var list<string> */
    #[Url(except: [])]
    public $condition = [];

    #[Url(except: '')]
    public string $building = '';

    #[Url(except: '')]
    public string $sea = '';

    /** @var list<string> */
    #[Url(except: [])]
    public $amenities = [];

    #[Url(except: '')]
    public string $location = '';

    #[Url(except: false)]
    public bool $photos = false;

    protected function model(): string
    {
        return Property::class;
    }

    protected function module(): ListingModule
    {
        return ListingModule::Properties;
    }

    protected function relations(): array
    {
        return ['media', 'location', 'businessProfile.media'];
    }

    protected function viewName(): string
    {
        return 'livewire.listings.properties';
    }

    protected function filterProperties(): array
    {
        return ['deal', 'type', 'currency', 'price_min', 'price_max', 'area_min', 'area_max', 'rooms', 'floor_min', 'floor_max', 'condition', 'building', 'sea', 'amenities', 'location', 'photos'];
    }

    protected function sortOptions(): array
    {
        return [
            'recommended' => __('site.sort.recommended'),
            'newest' => __('site.sort.newest'),
            'price_asc' => __('site.sort.price_asc'),
            'price_desc' => __('site.sort.price_desc'),
            'area_desc' => __('site.sort.area_desc'),
            'sea' => __('site.sort.sea'),
        ];
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->latest('published_at'),
            'price_asc' => $query->orderByRaw('price_gel asc nulls last'),
            'price_desc' => $query->orderByRaw('price_gel desc nulls last'),
            'area_desc' => $query->orderByRaw('area_m2 desc nulls last'),
            'sea' => $query->orderByRaw('distance_to_sea_m asc nulls last'),
            default => $query->orderByPromotion(),
        };
    }

    protected function applyFilters(Builder $query): void
    {
        if (PropertyDeal::tryFrom($this->deal) !== null) {
            $query->where('deal', $this->deal);
        }

        $types = array_values(array_intersect($this->strings($this->type), PropertyType::values()));

        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        // Prices are compared in GEL (price_gel), converting the visitor's USD bounds at today's rate.
        $rate = $this->currency === 'USD' ? app(ExchangeRates::class)->rate(Currency::USD) : 1.0;

        if (($min = $this->number($this->price_min)) !== null) {
            $query->where('price_gel', '>=', $min * $rate);
        }

        if (($max = $this->number($this->price_max)) !== null && $max > 0) {
            $query->where('price_gel', '<=', $max * $rate);
        }

        if (($min = $this->number($this->area_min)) !== null) {
            $query->where('area_m2', '>=', $min);
        }

        if (($max = $this->number($this->area_max)) !== null && $max > 0) {
            $query->where('area_m2', '<=', $max);
        }

        $rooms = array_values(array_intersect($this->strings($this->rooms), self::ROOM_OPTIONS));

        if ($rooms !== []) {
            $query->where(function (Builder $query) use ($rooms): void {
                $exact = array_map('intval', array_diff($rooms, ['4']));

                if ($exact !== []) {
                    $query->orWhereIn('rooms', $exact);
                }

                if (in_array('4', $rooms, true)) {
                    $query->orWhere('rooms', '>=', 4);
                }
            });
        }

        if (is_numeric($this->floor_min)) {
            $query->where('floor', '>=', (int) $this->floor_min);
        }

        if (is_numeric($this->floor_max)) {
            $query->where('floor', '<=', (int) $this->floor_max);
        }

        if ($this->location !== '') {
            $query->whereIn('location_id', $this->locationIds($this->location));
        }

        $conditions = array_values(array_intersect($this->strings($this->condition), PropertyCondition::values()));

        if ($conditions !== []) {
            $query->whereIn('condition', $conditions);
        }

        if (BuildingStatus::tryFrom($this->building) !== null) {
            $query->where('building_status', $this->building);
        }

        if (in_array((int) $this->sea, self::SEA_OPTIONS, true)) {
            $query->where('distance_to_sea_m', '<=', (int) $this->sea);
        }

        foreach (array_slice($this->strings($this->amenities), 0, 12) as $slug) {
            $query->whereHas('features', fn (Builder $feature) => $feature->where('slug', $slug));
        }

        if ($this->photos) {
            $this->filterPhotosOnly($query);
        }
    }

    public function mount(): void
    {
        $routeType = request()->route('type');
        $routeDeal = request()->route('deal');

        if (is_string($routeType) && PropertyType::tryFrom($routeType) !== null && $this->strings($this->type) === []) {
            $this->type = [$routeType];
        }

        if (is_string($routeDeal) && PropertyDeal::tryFrom($routeDeal) !== null && $this->deal === '') {
            $this->deal = $routeDeal;
        }

        parent::mount();
    }

    protected function card(Model $model): array
    {
        return app(CardPresenter::class)->property($model);
    }

    protected function filterOptions(): array
    {
        return [
            'dealOptions' => PropertyDeal::options(),
            'typeOptions' => PropertyType::options(),
            'conditionOptions' => PropertyCondition::options(),
            'buildingOptions' => BuildingStatus::options(),
            'amenityOptions' => $this->amenityOptions(),
            'locationOptions' => $this->locationOptions(),
        ];
    }

    protected function chips(): array
    {
        $price = app(PriceFormatter::class);
        $chips = [];

        if (($deal = PropertyDeal::tryFrom($this->deal)) !== null) {
            $chips[] = ['label' => $deal->getLabel(), 'property' => 'deal'];
        }

        foreach ($this->strings($this->type) as $type) {
            $chips[] = ['label' => PropertyType::tryFrom($type)?->getLabel() ?? $type, 'property' => 'type', 'value' => $type];
        }

        if ($this->price_min !== '' || $this->price_max !== '') {
            $chips[] = ['label' => $this->rangeLabel($price, $this->price_min, $this->price_max, $this->currency === 'USD' ? 'USD' : 'GEL'), 'property' => 'price_min'];
        }

        if ($this->area_min !== '' || $this->area_max !== '') {
            $chips[] = ['label' => $this->plainRange($this->area_min, $this->area_max, ' m²'), 'property' => 'area_min'];
        }

        foreach ($this->strings($this->rooms) as $rooms) {
            $chips[] = ['label' => $rooms === '4' ? __('site.filters.rooms_4_plus') : trans_choice('listing.rooms', (int) $rooms, ['count' => $rooms]), 'property' => 'rooms', 'value' => $rooms];
        }

        if ($this->floor_min !== '' || $this->floor_max !== '') {
            $chips[] = ['label' => __('site.filters.floor').': '.$this->plainRange($this->floor_min, $this->floor_max), 'property' => 'floor_min'];
        }

        foreach ($this->strings($this->condition) as $condition) {
            $chips[] = ['label' => PropertyCondition::tryFrom($condition)?->getLabel() ?? $condition, 'property' => 'condition', 'value' => $condition];
        }

        if (($building = BuildingStatus::tryFrom($this->building)) !== null) {
            $chips[] = ['label' => $building->getLabel(), 'property' => 'building'];
        }

        if (in_array((int) $this->sea, self::SEA_OPTIONS, true)) {
            $chips[] = ['label' => __('site.filters.sea_up_to', ['distance' => $this->sea]), 'property' => 'sea'];
        }

        $amenityLabels = $this->amenityOptions();

        foreach ($this->strings($this->amenities) as $slug) {
            $chips[] = ['label' => $amenityLabels[$slug] ?? $slug, 'property' => 'amenities', 'value' => $slug];
        }

        if ($this->location !== '') {
            $chips[] = ['label' => $this->locationLabel($this->location), 'property' => 'location'];
        }

        if ($this->photos) {
            $chips[] = ['label' => __('site.filters.photos_only'), 'property' => 'photos'];
        }

        return $chips;
    }

    public function removeFilter(string $property, ?string $value = null): void
    {
        match ($property) {
            'price_min' => $this->reset('price_max'),
            'area_min' => $this->reset('area_max'),
            'floor_min' => $this->reset('floor_max'),
            default => null,
        };

        parent::removeFilter($property, $value);
    }

    /**
     * @return array<string, string>
     */
    private function amenityOptions(): array
    {
        return Amenity::query()->forScope(AmenityScope::Property)->where('is_filterable', true)->get()
            ->mapWithKeys(fn (Amenity $amenity): array => [$amenity->slug => (string) $amenity->name])->all();
    }

    private function plainRange(string $min, string $max, string $unit = ''): string
    {
        return match (true) {
            $min !== '' && $max !== '' => $min.'–'.$max.$unit,
            $min !== '' => __('site.filters.from', ['value' => $min.$unit]),
            default => __('site.filters.up_to', ['value' => $max.$unit]),
        };
    }
}
