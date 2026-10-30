<?php

namespace App\Http\Controllers\Site;

use App\Enums\CategoryModule;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ShowsListing;
use App\Models\Category;
use App\Models\Event;
use App\Services\DateFormatter;
use App\Services\Favorites;
use App\Services\Seo\StructuredData;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\Contact;
use App\Support\Listings\DetailPresenter;
use App\Support\Listings\ListingQuery;
use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Events: a month calendar and an upcoming list, by category.
 */
class EventController extends Controller
{
    use ShowsListing;

    public function index(Request $request, CardPresenter $cards, DateFormatter $dates): View
    {
        $mode = $request->query('view') === 'calendar' ? 'calendar' : 'list';
        $category = Category::query()->forModule(CategoryModule::Events)->where('slug', (string) $request->query('category'))->first();

        try {
            $month = Carbon::createFromFormat('Y-m', (string) $request->query('month', now()->format('Y-m')))?->startOfMonth() ?? now()->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }

        $base = fn (): Builder => ListingQuery::public(Event::query())
            ->with(['media', 'location', 'category', 'place'])
            ->when($category, fn (Builder $query) => $query->where('category_id', $category->id));

        $calendar = [];
        $monthCards = collect();

        if ($mode === 'calendar') {
            $end = $month->copy()->endOfMonth();
            $events = $base()
                ->where('starts_at', '<=', $end)
                ->where(fn (Builder $query) => $query->where('ends_at', '>=', $month)->orWhere(fn (Builder $query) => $query->whereNull('ends_at')->where('starts_at', '>=', $month)))
                ->orderBy('starts_at')
                ->get();

            for ($day = $month->copy(); $day->lte($end); $day->addDay()) {
                $calendar[] = [
                    'date' => $day->toDateString(),
                    'day' => $day->day,
                    'today' => $day->isToday(),
                    'past' => $day->lt(today()),
                    'events' => $events->filter(fn (Event $event): bool => $event->starts_at->copy()->startOfDay()->lte($day) && ($event->ends_at ?? $event->starts_at)->copy()->endOfDay()->gte($day))
                        ->map(fn (Event $event): array => ['title' => (string) $event->title, 'url' => localized_route('events.show', [$event]), 'time' => $event->is_all_day ? null : $event->starts_at->format('H:i')])
                        ->values()->all(),
                ];
            }
        }

        if ($mode === 'calendar') {
            $monthCards = $events->map(fn (Event $event) => $cards->event($event));
        }

        $list = $mode === 'list' ? $base()->upcoming()->paginate(12)->withQueryString() : null;

        return view('pages.events.index', [
            'mode' => $mode,
            'month' => $month,
            'monthLabel' => $dates->monthYear($month),
            'calendar' => $calendar,
            'monthCards' => $monthCards,
            'offset' => $month->dayOfWeekIso - 1,
            'list' => $list,
            'cards' => $list?->getCollection()->map(fn (Event $event) => $cards->event($event)) ?? collect(),
            'category' => $category,
            'categories' => Category::query()->forModule(CategoryModule::Events)->where('is_active', true)->orderBy('sort')->get(),
        ]);
    }

    public function show(Event $event, CardPresenter $cards, DetailPresenter $detail, Favorites $favorites, StructuredData $seo): View
    {
        $preview = $this->visit($event);
        $event->load(['media', 'location', 'category', 'place', 'businessProfile', 'user']);
        $card = $cards->event($event);

        return view('pages.events.show', [
            'seo' => $seo->for($event),
            'event' => $event,
            'card' => $card,
            'preview' => $preview,
            'gallery' => $detail->gallery($event),
            'body' => SafeHtml::clean((string) $event->description),
            'hasPhone' => Contact::phone($event) !== null,
            'owner' => $detail->owner($event),
            'more' => ListingQuery::public(Event::query())->with(['media', 'location', 'category', 'place'])->whereKeyNot($event->id)->upcoming()->limit(3)->get()->map(fn (Event $item) => $cards->event($item)),
            'saved' => $favorites->isSaved($event),
            'calendarUrl' => $this->googleCalendarUrl($event),
            'description' => $detail->description($event),
        ]);
    }

    private function googleCalendarUrl(Event $event): string
    {
        $format = $event->is_all_day ? 'Ymd' : 'Ymd\THis\Z';
        $start = $event->starts_at->copy()->utc();
        $end = ($event->ends_at ?? $event->starts_at->copy()->addHours($event->is_all_day ? 24 : 2))->copy()->utc();

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => (string) $event->title,
            'dates' => $start->format($format).'/'.$end->format($format),
            'location' => $event->venue ? (string) $event->venue : ($event->place !== null ? $event->place->title : ''),
        ]);
    }
}
