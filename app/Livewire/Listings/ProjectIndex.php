<?php

namespace App\Livewire\Listings;

use App\Contracts\ExchangeRates;
use App\Enums\Currency;
use App\Enums\ListingModule;
use App\Models\Project;
use App\Services\PriceFormatter;
use App\Support\Listings\CardPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * /real-estate/projects: district, starting price per m², keyword. No property type.
 *
 * @extends ListingIndex<Project>
 */
class ProjectIndex extends ListingIndex
{
    #[Url(except: 'GEL')]
    public string $currency = 'GEL';

    #[Url(except: '')]
    public string $price_min = '';

    #[Url(except: '')]
    public string $price_max = '';

    #[Url(except: '')]
    public string $location = '';

    protected function model(): string
    {
        return Project::class;
    }

    protected function module(): ListingModule
    {
        return ListingModule::Projects;
    }

    protected function relations(): array
    {
        return ['media', 'location', 'businessProfile.media'];
    }

    protected function viewName(): string
    {
        return 'livewire.listings.projects';
    }

    protected function filterProperties(): array
    {
        return ['currency', 'price_min', 'price_max', 'location'];
    }

    protected function sortOptions(): array
    {
        return [
            'recommended' => __('site.sort.recommended'),
            'newest' => __('site.sort.newest'),
            'price_asc' => __('site.sort.price_asc'),
            'price_desc' => __('site.sort.price_desc'),
            'sea' => __('site.sort.sea'),
        ];
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->latest('published_at'),
            'price_asc' => $query->orderByRaw('price_gel asc nulls last'),
            'price_desc' => $query->orderByRaw('price_gel desc nulls last'),
            'sea' => $query->orderByRaw('distance_to_sea_m asc nulls last'),
            default => $query->orderByPromotion(),
        };
    }

    protected function applyFilters(Builder $query): void
    {
        $rate = $this->currency === 'USD' ? app(ExchangeRates::class)->rate(Currency::USD) : 1.0;

        if (($min = $this->number($this->price_min)) !== null) {
            $query->where('price_gel', '>=', $min * $rate);
        }

        if (($max = $this->number($this->price_max)) !== null && $max > 0) {
            $query->where('price_gel', '<=', $max * $rate);
        }

        if ($this->location !== '') {
            $query->whereIn('location_id', $this->locationIds($this->location));
        }
    }

    protected function card(Model $model): array
    {
        return app(CardPresenter::class)->project($model);
    }

    protected function filterOptions(): array
    {
        return ['locationOptions' => $this->locationOptions()];
    }

    protected function chips(): array
    {
        $price = app(PriceFormatter::class);
        $chips = [];

        if ($this->price_min !== '' || $this->price_max !== '') {
            $chips[] = ['label' => $this->rangeLabel($price, $this->price_min, $this->price_max, $this->currency === 'USD' ? 'USD' : 'GEL'), 'property' => 'price_min'];
        }

        if ($this->location !== '') {
            $chips[] = ['label' => $this->locationLabel($this->location), 'property' => 'location'];
        }

        return $chips;
    }

    public function removeFilter(string $property, ?string $value = null): void
    {
        if ($property === 'price_min') {
            $this->reset('price_max');
        }

        parent::removeFilter($property, $value);
    }
}
