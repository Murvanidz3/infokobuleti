<?php

namespace App\Livewire\Listings;

use App\Enums\ListingModule;
use App\Enums\NotificationChannel;
use App\Enums\SearchFrequency;
use App\Models\Location;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\PriceFormatter;
use App\Support\Listings\ListingQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A module list page: filters synced to the URL, sorting, grid/list/map views, infinite scroll on
 * top of real page links, removable filter chips and "save this search".
 *
 * Subclasses declare their filters as #[Url] properties and list them in filterProperties().
 *
 * @template TModel of Model
 */
abstract class ListingIndex extends Component
{
    use WithPagination;

    public const int PER_PAGE = 12;

    public const int MAX_LOADED_PAGES = 8;

    public const int MAP_LIMIT = 400;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: 'recommended')]
    public string $sort = 'recommended';

    /** Grid, list or map (?view= in the URL). */
    #[Url(as: 'view', except: 'grid')]
    public string $display = 'grid';

    /** "south,west,north,east" set by "search in this area" on the map. */
    #[Url(except: '')]
    public string $bounds = '';

    /** Saved search being edited from the account (?saved=ID): "save" updates it instead. */
    #[Url(as: 'saved', except: 0)]
    public int $editingSearch = 0;

    /** Pages shown at once: grows as the visitor scrolls (infinite scroll). */
    public int $loaded = 1;

    /** The page's own URL, for pagination links built during Livewire updates. */
    #[Locked]
    public string $path = '';

    /**
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    abstract protected function module(): ListingModule;

    /**
     * Relations the cards need.
     *
     * @return list<string>
     */
    abstract protected function relations(): array;

    /**
     * @param  Builder<TModel>  $query
     */
    abstract protected function applyFilters(Builder $query): void;

    /**
     * @return array<string, string> key => label; the first one is the default ("recommended")
     */
    abstract protected function sortOptions(): array;

    /**
     * @param  Builder<TModel>  $query
     */
    abstract protected function applySort(Builder $query, string $sort): void;

    /**
     * @param  TModel  $model
     * @return array<string, mixed>
     */
    abstract protected function card(Model $model): array;

    /**
     * Active filters as removable chips.
     *
     * @return list<array{label: string, property: string, value?: string}>
     */
    abstract protected function chips(): array;

    /**
     * Names of the #[Url] filter properties (reset together, stored by "save this search").
     *
     * @return list<string>
     */
    abstract protected function filterProperties(): array;

    abstract protected function viewName(): string;

    /**
     * Extra data for the filter panel (options lists).
     *
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [];
    }

    /**
     * Views offered by the toggle.
     *
     * @return list<string>
     */
    protected function views(): array
    {
        return ['grid', 'list', 'map'];
    }

    public function mount(): void
    {
        $this->path = url()->current();

        // Hand-edited URLs ("?type=hotel" instead of "?type[]=hotel"): array filters stay arrays.
        $defaults = $this->defaults();

        foreach ($this->filterProperties() as $property) {
            if (is_array($defaults[$property] ?? null) && ! is_array($this->{$property})) {
                $this->{$property} = $this->{$property} === null || $this->{$property} === '' ? [] : [(string) $this->{$property}];
            }
        }

        if (! array_key_exists($this->sort, $this->sortOptions())) {
            $this->sort = 'recommended';
        }

        if (! in_array($this->display, $this->views(), true)) {
            $this->display = $this->views()[0];
        }
    }

    public function updated(string $property): void
    {
        $base = explode('.', $property)[0];

        if (in_array($base, [...$this->filterProperties(), 'q', 'sort', 'bounds'], true)) {
            $this->resetPage();
            $this->loaded = 1;
        }

        if ($base === 'display' && ! in_array($this->display, $this->views(), true)) {
            $this->display = $this->views()[0];
        }

        if ($base === 'sort' && ! array_key_exists($this->sort, $this->sortOptions())) {
            $this->sort = 'recommended';
        }
    }

    public function loadMore(): void
    {
        $this->loaded = min($this->loaded + 1, self::MAX_LOADED_PAGES);
    }

    public function removeFilter(string $property, ?string $value = null): void
    {
        if (! in_array($property, [...$this->filterProperties(), 'q', 'bounds'], true)) {
            return;
        }

        $current = $this->{$property};

        if (is_array($current) && $value !== null) {
            $this->{$property} = array_is_list($current) ? array_values(array_diff($current, [$value])) : array_diff_key($current, [$value => true]);
        } else {
            $this->reset($property);
        }

        $this->updated($property);
    }

    public function resetFilters(): void
    {
        $this->reset([...$this->filterProperties(), 'q', 'bounds']);
        $this->updated('q');
    }

    public function searchArea(float $south, float $west, float $north, float $east): void
    {
        if ($south >= $north || $west >= $east) {
            return;
        }

        $this->bounds = implode(',', array_map(fn (float $value): string => (string) round($value, 5), [$south, $west, $north, $east]));
        $this->updated('bounds');
    }

    public function saveSearch(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            $this->dispatch('require-login');

            return;
        }

        $filters = $this->activeFilters();
        $name = collect($this->chips())->pluck('label')->take(3)->implode(', ');

        if ($this->editingSearch > 0) {
            $search = SavedSearch::query()->where('user_id', $user->id)->where('module', $this->module())->find($this->editingSearch);

            if ($search !== null) {
                $search->update(['filters' => $filters, 'name' => mb_substr($name !== '' ? $name : __('nav.'.$this->navKey()), 0, 120)]);
                $this->dispatch('toast', message: __('site.list.search_updated'), type: 'success');

                return;
            }

            $this->editingSearch = 0;
        }

        $exists = SavedSearch::query()
            ->where('user_id', $user->id)
            ->where('module', $this->module())
            ->whereRaw('filters = ?::jsonb', [json_encode($filters, JSON_UNESCAPED_UNICODE)])
            ->exists();

        if (! $exists) {
            SavedSearch::query()->create([
                'user_id' => $user->id,
                'module' => $this->module(),
                'filters' => $filters,
                'name' => mb_substr($name !== '' ? $name : __('nav.'.$this->navKey()), 0, 120),
                'channels' => [NotificationChannel::Email->value],
                'frequency' => SearchFrequency::Daily,
                'is_active' => true,
            ]);
        }

        $this->dispatch('toast', message: __('site.list.search_saved'), type: 'success');
    }

    /**
     * Non-default filter values, as they appear in the URL.
     *
     * @return array<string, mixed>
     */
    public function activeFilters(): array
    {
        $defaults = $this->defaults();

        return array_filter(
            $this->only([...$this->filterProperties(), 'q', 'bounds']),
            fn (mixed $value, string $key): bool => $value !== ($defaults[$key] ?? null) && $value !== '' && $value !== [] && $value !== null,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function render(): View
    {
        $query = $this->query();
        $total = (clone $query)->count();
        $page = max(1, (int) $this->getPage());
        $page = min($page, max(1, (int) ceil($total / self::PER_PAGE)));

        $sorted = (clone $query)->with($this->relations());
        $this->applySearchOrSort($sorted);

        $models = $sorted->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE * $this->loaded)->get();
        $items = $models->map(fn (Model $model): array => $this->card($model));

        $paginator = (new LengthAwarePaginator($models, $total, self::PER_PAGE, $page))
            ->withPath($this->path)
            ->appends(array_filter([
                ...$this->activeFilters(),
                'sort' => $this->sort !== 'recommended' ? $this->sort : null,
                'view' => $this->display !== $this->views()[0] ? $this->display : null,
            ], fn (mixed $value): bool => $value !== null));

        $markers = $this->display === 'map' ? $this->markers($query) : [];

        if ($this->display === 'map') {
            $this->dispatch('markers-updated', markers: $markers);
        }

        return view($this->viewName(), [
            'items' => $items,
            'total' => $total,
            'paginator' => $paginator,
            'hasMore' => $page + $this->loaded - 1 < $paginator->lastPage() && $this->loaded < self::MAX_LOADED_PAGES,
            'chips' => $this->chips(),
            'sortOptions' => $this->sortOptions(),
            'views' => $this->views(),
            'markers' => $markers,
            'targets' => implode(',', [...$this->filterProperties(), 'q', 'sort', 'display', 'bounds', 'removeFilter', 'resetFilters', 'searchArea']),
            'activeCount' => count($this->activeFilters()) - ($this->q !== '' ? 1 : 0) - ($this->bounds !== '' ? 1 : 0),
            ...$this->filterOptions(),
        ]);
    }

    /**
     * Published items matching every filter (no ordering).
     *
     * @return Builder<TModel>
     */
    protected function query(): Builder
    {
        $class = $this->model();
        $query = ListingQuery::public($class::query());

        $this->applyFilters($query);

        if ($this->bounds !== '' && preg_match('/^-?[\d.]+,-?[\d.]+,-?[\d.]+,-?[\d.]+$/', $this->bounds) === 1) {
            [$south, $west, $north, $east] = array_map('floatval', explode(',', $this->bounds));
            $query->scopes(['withinBounds' => [$south, $west, $north, $east]]);
        }

        if (trim($this->q) !== '') {
            $query->whereKey($this->searchIds());
        }

        return $query;
    }

    /**
     * @param  Builder<TModel>  $query
     */
    private function applySearchOrSort(Builder $query): void
    {
        if (trim($this->q) !== '' && $this->sort === 'recommended') {
            $ids = $this->searchIds();

            if ($ids !== []) {
                $query->orderByRaw('array_position(?::bigint[], '.$query->getModel()->qualifyColumn('id').')', ['{'.implode(',', $ids).'}']);

                return;
            }
        }

        $this->applySort($query, $this->sort);
        $query->orderByDesc($query->getModel()->qualifyColumn('id'));
    }

    /** @var list<int>|null */
    private ?array $searchIds = null;

    /**
     * Matching ids, best match first, from the search engine (Meilisearch or PostgreSQL).
     *
     * @return list<int>
     */
    protected function searchIds(): array
    {
        if ($this->searchIds !== null) {
            return $this->searchIds;
        }

        $class = $this->model();

        /** @var \Laravel\Scout\Builder<TModel> $search */
        $search = call_user_func([$class, 'search'], mb_substr(trim($this->q), 0, 100));

        return $this->searchIds = $search
            ->query(fn (Builder $query) => ListingQuery::public($query))
            ->take(500)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Pins for the map view, from the same filtered query.
     *
     * @param  Builder<TModel>  $query
     * @return list<array{lat: float, lng: float, pin: ?string, title: string, url: string, image: ?string, meta: ?string}>
     */
    protected function markers(Builder $query): array
    {
        return (clone $query)
            ->with($this->relations())
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->limit(self::MAP_LIMIT)
            ->get()
            ->map(function (Model $model): array {
                $card = $this->card($model);

                return [
                    'lat' => (float) $card['lat'],
                    'lng' => (float) $card['lng'],
                    'pin' => $card['pin'] ?? null,
                    'title' => (string) ($card['name'] ?? $card['title']),
                    'url' => (string) $card['url'],
                    'image' => $card['image'] ?? null,
                    'meta' => $card['price'] ?? $card['salary'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Initial values of the component's properties (what "no filter" means).
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return (new \ReflectionClass($this))->getDefaultProperties();
    }

    protected function navKey(): string
    {
        return match ($this->module()) {
            ListingModule::Properties => 'real_estate',
            ListingModule::Projects => 'projects',
            ListingModule::Classifieds => 'classifieds',
            ListingModule::Vacancies => 'jobs',
        };
    }

    /**
     * Ids of a location and its children, by slug.
     *
     * @return list<int>
     */
    protected function locationIds(string $slug): array
    {
        $location = Location::query()->where('slug', $slug)->first();

        if ($location === null) {
            return [0];
        }

        return [$location->id, ...$location->children()->pluck('id')->map(fn ($id): int => (int) $id)->all()];
    }

    /**
     * Active locations for the location select: slug => name.
     *
     * @return array<string, string>
     */
    protected function locationOptions(): array
    {
        return Location::query()->active()->whereNotNull('parent_id')->orderBy('sort')->get()
            ->mapWithKeys(fn (Location $location): array => [$location->slug => (string) $location->name])
            ->all();
    }

    protected function locationLabel(string $slug): string
    {
        return $this->locationOptions()[$slug] ?? $slug;
    }

    /**
     * @param  Builder<TModel>  $query
     */
    protected function filterPhotosOnly(Builder $query): void
    {
        $query->whereHas('media', fn (Builder $media) => $media->where('collection_name', 'gallery'));
    }

    /**
     * Normalises a number filter typed by the visitor.
     */
    protected function number(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value >= 0 ? (float) $value : null;
    }

    /**
     * @return list<string>
     */
    protected function strings(mixed $values): array
    {
        $values = $values instanceof Collection ? $values->all() : (array) $values;

        return array_values(array_filter(array_map(fn (mixed $value): string => is_scalar($value) ? (string) $value : '', $values), fn (string $value): bool => $value !== ''));
    }

    protected function rangeLabel(PriceFormatter $price, string $min, string $max, string $currency = 'GEL'): string
    {
        return match (true) {
            $min !== '' && $max !== '' => $price->range((float) $min, (float) $max, $currency),
            $min !== '' => __('listing.price_from', ['price' => $price->format((float) $min, $currency)]),
            default => __('site.filters.up_to', ['value' => $price->format((float) $max, $currency)]),
        };
    }
}
