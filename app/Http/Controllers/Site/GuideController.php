<?php

namespace App\Http\Controllers\Site;

use App\Enums\PlaceType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ShowsListing;
use App\Models\Event;
use App\Models\Place;
use App\Services\Favorites;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\Contact;
use App\Support\Listings\DetailPresenter;
use App\Support\Listings\ListingQuery;
use App\Support\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * City guide: places by type (beaches, sights, nature, restaurants…) and place pages.
 */
class GuideController extends Controller
{
    use ShowsListing;

    public function index(Request $request, CardPresenter $cards): View
    {
        $type = PlaceType::tryFrom((string) $request->query('type'));

        $places = ListingQuery::public(Place::query())
            ->with(['media', 'location'])
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderByRaw('rating_avg desc nulls last')
            ->orderBy('id')
            ->paginate(18)
            ->withQueryString();

        $counts = ListingQuery::public(Place::query())->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return view('pages.guide.index', [
            'type' => $type,
            'types' => collect(PlaceType::cases())->filter(fn (PlaceType $case) => ($counts[$case->value] ?? 0) > 0)->values(),
            'counts' => $counts,
            'places' => $places,
            'cards' => $places->getCollection()->map(fn (Place $place) => $cards->guide($place)),
            'markers' => $places->getCollection()->filter(fn (Place $place) => $place->lat !== null)->map(fn (Place $place): array => [
                'lat' => (float) $place->lat,
                'lng' => (float) $place->lng,
                'pin' => null,
                'title' => (string) $place->title,
                'url' => localized_route('guide.show', [$place]),
                'image' => $place->coverUrl('thumb'),
                'meta' => (string) $place->type->getLabel(),
            ])->values()->all(),
            'events' => ListingQuery::public(Event::query())->with(['media', 'location', 'category', 'place'])->upcoming()->limit(3)->get()->map(fn (Event $event) => $cards->event($event)),
        ]);
    }

    public function show(Place $place, CardPresenter $cards, DetailPresenter $detail, Favorites $favorites): View
    {
        $preview = $this->visit($place);
        $place->load(['media', 'location', 'businessProfile', 'user']);

        $hours = is_array($place->opening_hours) ? $place->opening_hours : [];

        return view('pages.guide.show', [
            'place' => $place,
            'preview' => $preview,
            'gallery' => $detail->gallery($place),
            'facts' => array_values(array_filter([
                ['icon' => 'compass', 'label' => __('site.filters.type'), 'value' => (string) $place->type->getLabel()],
                $place->price_level ? ['icon' => 'tag', 'label' => __('site.guide.price_level'), 'value' => str_repeat('₾', (int) $place->price_level)] : null,
                $place->rating_avg ? ['icon' => 'star', 'label' => __('listing.rating_label'), 'value' => number_format((float) $place->rating_avg, 1)] : null,
                $place->website ? ['icon' => 'globe', 'label' => __('site.guide.website'), 'value' => (string) parse_url((string) $place->website, PHP_URL_HOST)] : null,
            ])),
            'content' => SafeHtml::clean((string) $place->content),
            'hours' => $this->hours($hours),
            'hasPhone' => Contact::phone($place) !== null,
            'nearby' => $detail->nearby($place),
            'reviews' => $detail->reviews($place),
            'events' => ListingQuery::public($place->events()->getQuery())->with(['media', 'location', 'category', 'place'])->upcoming()->limit(3)->get()->map(fn (Event $event) => $cards->event($event)),
            'similar' => ListingQuery::public(Place::query())->with(['media', 'location'])->whereKeyNot($place->id)->where('type', $place->type)->orderByRaw('rating_avg desc nulls last')->limit(4)->get()->map(fn (Place $item) => $cards->guide($item)),
            'saved' => $favorites->isSaved($place),
            'address' => $cards->locationText($place),
            'description' => $detail->description($place),
        ]);
    }

    /**
     * Opening hours per weekday (Monday first): "09:00–18:00", "closed" or null when unknown.
     *
     * @param  array<string, mixed>  $hours
     * @return list<array{day: string, text: ?string, today: bool}>
     */
    private function hours(array $hours): array
    {
        if ($hours === []) {
            return [];
        }

        $keys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        return array_map(function (string $key, int $index) use ($hours): array {
            $ranges = $hours[$key] ?? null;
            $text = match (true) {
                ! is_array($ranges) => null,
                $ranges === [] => __('site.guide.closed'),
                default => implode(', ', array_map(fn ($range): string => is_array($range) ? implode('–', $range) : (string) $range, $ranges)),
            };

            return ['day' => __('time.weekdays.'.(($index + 1) % 7)), 'text' => $text, 'today' => now()->dayOfWeekIso === $index + 1];
        }, $keys, array_keys($keys));
    }
}
