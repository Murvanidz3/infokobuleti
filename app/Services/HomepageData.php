<?php

namespace App\Services;

use App\Enums\CategoryModule;
use App\Enums\PlaceType;
use App\Enums\PropertyType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\Vacancy;
use App\Services\Info\UsefulInfo;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\ListingQuery;
use App\Support\Listings\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Homepage blocks from real listings: curated and promoted items first, cached for five minutes
 * (and dropped as soon as a listing changes, see PublicCache). The per-visitor "saved" state is
 * applied after the cache.
 */
class HomepageData
{
    public function __construct(
        private CardPresenter $cards,
        private PriceFormatter $price,
        private Settings $settings,
        private Favorites $favorites,
        private UsefulInfo $info,
    ) {}

    /**
     * @return list<array{name: string, count: string, icon: string, url: string}>
     */
    public function categories(): array
    {
        $counts = PublicCache::remember('home.counts', fn (): array => [
            'hotels' => ListingQuery::public(Hotel::query())->count(),
            'apartments' => ListingQuery::public(Property::query())->where('type', PropertyType::Apartment)->count(),
            'houses' => ListingQuery::public(Property::query())->where('type', PropertyType::House)->count(),
            'cars' => ListingQuery::public(Classified::query())->whereIn('category_id', $this->categoryTree('cars'))->count(),
            'services' => ListingQuery::public(Classified::query())->whereIn('category_id', $this->categoryTree('services'))->count(),
            'jobs' => ListingQuery::public(Vacancy::query())->count(),
            'restaurants' => ListingQuery::public(Place::query())->whereIn('type', [PlaceType::Restaurant, PlaceType::Cafe])->count(),
            'events' => ListingQuery::public(Event::query())->upcoming()->count(),
        ], 'all');

        $tiles = [
            'hotels' => ['listings', 'bed', localized_route('hotels.index')],
            'apartments' => ['listings', 'building', localized_route('real-estate.index', ['type' => ['apartment']])],
            'houses' => ['listings', 'house', localized_route('real-estate.index', ['type' => ['house']])],
            'cars' => ['listings', 'car', localized_route('classifieds.index', ['category' => 'cars'])],
            'services' => ['listings', 'wrench', localized_route('classifieds.index', ['category' => 'services'])],
            'jobs' => ['vacancies', 'briefcase', localized_route('jobs.index')],
            'restaurants' => ['places', 'fork', localized_route('guide.index', ['type' => 'restaurant'])],
            'events' => ['events', 'calendar', localized_route('events.index')],
        ];

        return array_map(fn (string $key, array $tile): array => [
            'name' => __('site.home.categories.'.$key),
            'count' => trans_choice('ui.counts.'.$tile[0], $counts[$key], ['count' => $this->price->number($counts[$key])]),
            'icon' => $tile[1],
            'url' => $tile[2],
        ], array_keys($tiles), $tiles);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function hotels(): array
    {
        return $this->withSaved(PublicCache::remember('home.hotels.'.Hotel::rotationSeed(), fn (): array => $this->curated(
            Hotel::query(), 'homepage.featured_hotels', 8, withPaidSlots: true,
        )->map(fn (Hotel $hotel): array => $this->cards->hotel($hotel))->all()));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function properties(): array
    {
        return $this->withSaved(PublicCache::remember('home.properties.'.Property::rotationSeed(), fn (): array => $this->curated(
            Property::query(), 'homepage.featured_properties', 6,
        )->map(fn (Property $property): array => $this->cards->property($property))->all()));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function classifieds(): array
    {
        return PublicCache::remember('home.classifieds.'.Classified::rotationSeed(), fn (): array => ListingQuery::public(Classified::query())
            ->with(['media', 'location', 'category.parent'])
            ->orderByPromotionRotating()
            ->limit(5)
            ->get()
            ->map(fn (Classified $classified): array => $this->cards->classified($classified))
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function jobs(): array
    {
        return PublicCache::remember('home.jobs.'.Vacancy::rotationSeed(), fn (): array => ListingQuery::public(Vacancy::query())
            ->with(['location', 'businessProfile.media'])
            ->orderByPromotionRotating()
            ->limit(5)
            ->get()
            ->map(fn (Vacancy $vacancy): array => $this->cards->job($vacancy))
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function guide(): array
    {
        return PublicCache::remember('home.guide', fn (): array => ListingQuery::public(Place::query())
            ->with(['media', 'location'])
            ->whereIn('type', [PlaceType::Beach, PlaceType::Sight, PlaceType::Nature, PlaceType::Museum])
            ->orderByRaw('rating_avg desc nulls last')
            ->limit(5)
            ->get()
            ->map(fn (Place $place): array => $this->cards->guide($place))
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function usefulInfo(): array
    {
        return $this->info->data();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function news(): array
    {
        return PublicCache::remember('home.news', fn (): array => ListingQuery::public(Article::query())
            ->with(['media', 'category'])
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (Article $article): array => $this->cards->news($article))
            ->all());
    }

    /**
     * Top-level categories for the hero search selects, by module.
     *
     * @return array{classifieds: array<string, string>, jobs: array<string, string>}
     */
    public function searchOptions(): array
    {
        return PublicCache::remember('home.search-options', fn (): array => [
            'classifieds' => $this->topCategories(CategoryModule::Classifieds),
            'jobs' => $this->topCategories(CategoryModule::Vacancies),
        ]);
    }

    public function heroImage(): ?string
    {
        $season = $this->settings->get('homepage.season', 'auto');

        if ($season === 'auto') {
            $season = in_array(now()->month, [5, 6, 7, 8, 9], true) ? 'summer' : 'winter';
        }

        $path = $this->settings->get("homepage.hero.{$season}") ?? $this->settings->get('homepage.hero.summer');

        return is_string($path) && $path !== '' ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Admin-picked items in their order, then paid homepage slots (hotels), then promoted ones
     * taking turns, up to $limit.
     *
     * @template TModel of Hotel|Property
     *
     * @param  Builder<TModel>  $query
     * @return Collection<int, TModel>
     */
    private function curated(Builder $query, string $setting, int $limit, bool $withPaidSlots = false): Collection
    {
        $picked = array_values(array_filter(array_map('intval', (array) $this->settings->get($setting, []))));
        $relations = ['media', 'location', 'businessProfile.media'];

        $featured = $picked === [] ? new Collection : ListingQuery::public(clone $query)->with($relations)->whereKey($picked)->get()
            ->sortBy(fn (Model $model): int|false => array_search($model->getKey(), $picked, true))
            ->values();

        if ($withPaidSlots && $featured->count() < $limit) {
            $featured = $featured->merge(
                ListingQuery::public(clone $query)->with($relations)
                    ->whereKeyNot($featured->modelKeys())
                    ->featuredOnHome()
                    ->orderByRaw('md5(id::text || ?)', [(string) Hotel::rotationSeed()])
                    ->limit($limit - $featured->count())
                    ->get(),
            );
        }

        $rest = ListingQuery::public($query)->with($relations)
            ->whereKeyNot($featured->modelKeys())
            ->orderByPromotionRotating()
            ->limit(max(0, $limit - $featured->count()))
            ->get();

        /** @var Collection<int, TModel> $items */
        $items = $featured->merge($rest)->take($limit)->values();

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return list<array<string, mixed>>
     */
    private function withSaved(array $cards): array
    {
        return array_map(fn (array $card): array => [...$card, 'saved' => $this->favorites->isSavedKey((string) $card['favorite'])], $cards);
    }

    /**
     * @return list<int>
     */
    private function categoryTree(string $slug): array
    {
        $root = Category::query()->where('module', CategoryModule::Classifieds)->where('slug', $slug)->first();

        return $root === null ? [] : [$root->id, ...$root->children()->pluck('id')->all()];
    }

    /**
     * @return array<string, string> slug => name
     */
    private function topCategories(CategoryModule $module): array
    {
        return Category::query()->forModule($module)->whereNull('parent_id')->where('is_active', true)->orderBy('sort')->get()
            ->mapWithKeys(fn (Category $category): array => [$category->slug => (string) $category->name])
            ->all();
    }
}
