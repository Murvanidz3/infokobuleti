<?php

namespace App\Search;

use App\Models\Article;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\Vacancy;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\ListingQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Search across every module, grouped: header suggestions and the /search page.
 */
class SiteSearch
{
    /**
     * module key => [model class, relations, list route, nav label key]
     *
     * @var array<string, array{0: class-string<Model>, 1: list<string>, 2: string, 3: string}>
     */
    public const array MODULES = [
        'hotels' => [Hotel::class, ['media', 'location'], 'hotels.index', 'nav.hotels'],
        'real_estate' => [Property::class, ['media', 'location'], 'real-estate.index', 'nav.real_estate'],
        'classifieds' => [Classified::class, ['media', 'location', 'category.parent'], 'classifieds.index', 'nav.classifieds'],
        'jobs' => [Vacancy::class, ['location', 'businessProfile'], 'jobs.index', 'nav.jobs'],
        'guide' => [Place::class, ['media', 'location'], 'guide.index', 'nav.guide'],
        'events' => [Event::class, ['media', 'location', 'category', 'place'], 'events.index', 'nav.events'],
        'news' => [Article::class, ['media', 'category'], 'news.index', 'nav.news'],
    ];

    public function __construct(private CardPresenter $cards) {}

    /**
     * @return list<array{module: string, label: string, all: string, total: int, items: list<array{title: string, url: string, image: ?string, meta: string}>}>
     */
    public function grouped(string $query, int $perModule = 4): array
    {
        $query = mb_substr(trim($query), 0, 100);

        if (mb_strlen($query) < 2) {
            return [];
        }

        $groups = [];

        foreach (self::MODULES as $module => [$class, $relations, $route, $label]) {
            /** @var \Laravel\Scout\Builder<Model> $search */
            $search = call_user_func([$class, 'search'], $query);
            $ids = $search
                ->query(fn (Builder $builder) => ListingQuery::public($builder))
                ->take(50)
                ->keys()
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            if ($ids === []) {
                continue;
            }

            /** @var Builder<Model> $base */
            $base = $class::query();
            $models = ListingQuery::public($base)->with($relations)->whereKey(array_slice($ids, 0, $perModule))->get()
                ->sortBy(fn (Model $model): int|false => array_search((int) $model->getKey(), $ids, true))
                ->values();

            if ($models->isEmpty()) {
                continue;
            }

            $groups[] = [
                'module' => $module,
                'label' => __($label),
                'all' => in_array($module, ['guide', 'events', 'news'], true) ? localized_route('search', ['q' => $query, 'module' => $module]) : localized_route($route, ['q' => $query]),
                'total' => count($ids),
                'items' => $models->map(fn (Model $model): array => $this->item($model))->all(),
            ];
        }

        return $groups;
    }

    /**
     * @return array{title: string, url: string, image: ?string, meta: string}
     */
    public function item(Model $model): array
    {
        $card = match (true) {
            $model instanceof Hotel => $this->cards->hotel($model),
            $model instanceof Property => $this->cards->property($model),
            $model instanceof Classified => $this->cards->classified($model),
            $model instanceof Vacancy => $this->cards->job($model),
            $model instanceof Place => $this->cards->guide($model),
            $model instanceof Event => $this->cards->event($model),
            $model instanceof Article => $this->cards->news($model),
            default => throw new \InvalidArgumentException('Not a searchable model.'),
        };

        $meta = match (true) {
            $model instanceof Hotel => collect([$card['price'] ? $card['price'].' '.__('listing.per_night') : null, $card['address']])->filter()->implode(' · '),
            $model instanceof Property => $card['price'].' · '.$card['location'],
            $model instanceof Classified => $card['price'].' · '.$card['meta'],
            $model instanceof Vacancy => $card['salary'].' · '.$card['employer'],
            $model instanceof Place => $card['tag'],
            $model instanceof Event => $card['when'],
            default => $card['date'],
        };

        return [
            'title' => (string) ($card['name'] ?? $card['title']),
            'url' => (string) $card['url'],
            'image' => $card['image'] ?? null,
            'meta' => (string) $meta,
        ];
    }
}
