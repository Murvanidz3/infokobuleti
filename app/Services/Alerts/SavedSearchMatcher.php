<?php

namespace App\Services\Alerts;

use App\Enums\SearchFrequency;
use App\Livewire\Listings\ClassifiedIndex;
use App\Livewire\Listings\HotelIndex;
use App\Livewire\Listings\ListingIndex;
use App\Livewire\Listings\PropertyIndex;
use App\Livewire\Listings\VacancyIndex;
use App\Models\SavedSearch;
use App\Models\SavedSearchHit;
use App\Notifications\SavedSearchAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Saved-search alerts: finds listings published after a search was saved that match its filters
 * (the same query the list page runs), records them once, and sends them by the search's
 * frequency: instantly (every run), daily or weekly (from 09:00).
 */
class SavedSearchMatcher
{
    /** Listings older than this are never reported, even for a new search. */
    public const int LOOKBACK_DAYS = 3;

    /** At most this many listings in one alert. */
    public const int MAX_PER_ALERT = 10;

    /** @var array<string, class-string<ListingIndex<covariant Model>>> */
    private const array COMPONENTS = [
        'hotels' => HotelIndex::class,
        'properties' => PropertyIndex::class,
        'classifieds' => ClassifiedIndex::class,
        'vacancies' => VacancyIndex::class,
    ];

    /**
     * @return array{matched: int, sent: int}
     */
    public function run(?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $matched = 0;
        $sent = 0;

        SavedSearch::query()
            ->where('is_active', true)
            ->whereHas('user', fn (Builder $query) => $query->where('is_blocked', false))
            ->with('user')
            ->chunkById(100, function ($searches) use ($now, &$matched, &$sent): void {
                foreach ($searches as $search) {
                    $matched += $this->match($search, $now);

                    if ($this->due($search, $now)) {
                        $sent += $this->send($search, $now) ? 1 : 0;
                    }
                }
            });

        return ['matched' => $matched, 'sent' => $sent];
    }

    /**
     * Records new matches; returns how many.
     */
    public function match(SavedSearch $search, Carbon $now): int
    {
        $since = Carbon::parse($search->created_at ?? $now)->max($now->copy()->subDays(self::LOOKBACK_DAYS));
        $query = $this->query($search);
        $model = $query->getModel();

        $ids = $query
            ->where($model->qualifyColumn('published_at'), '>=', $since)
            ->where($model->qualifyColumn('user_id'), '!=', $search->user_id)
            ->whereNotIn($model->qualifyColumn('id'), SavedSearchHit::query()
                ->select('listing_id')
                ->where('saved_search_id', $search->id)
                ->where('listing_type', $model->getMorphClass()))
            ->limit(50)
            ->pluck($model->qualifyColumn('id'));

        foreach ($ids as $id) {
            SavedSearchHit::query()->insertOrIgnore([
                'saved_search_id' => $search->id,
                'listing_type' => $model->getMorphClass(),
                'listing_id' => $id,
                'created_at' => $now,
            ]);
        }

        return $ids->count();
    }

    /**
     * Published listings matching the saved filters (no date limit), as the list page builds them.
     *
     * @return Builder<Model>
     */
    public function query(SavedSearch $search): Builder
    {
        $component = app(self::COMPONENTS[$search->module->value]);

        foreach ((array) $search->filters as $property => $value) {
            if (property_exists($component, (string) $property)) {
                try {
                    $component->{$property} = $value;
                } catch (\TypeError) {
                    // A filter whose type changed since the search was saved is ignored.
                }
            }
        }

        /** @var Builder<Model> */
        return (fn (): Builder => $this->query())->call($component);
    }

    private function due(SavedSearch $search, Carbon $now): bool
    {
        $last = $search->last_notified_at;

        return match ($search->frequency) {
            SearchFrequency::Instant => true,
            SearchFrequency::Daily => $now->hour >= 9 && ($last === null || $last->lessThan($now->copy()->subHours(23))),
            SearchFrequency::Weekly => $now->hour >= 9 && $now->isMonday() && ($last === null || $last->lessThan($now->copy()->subDays(6))),
        };
    }

    private function send(SavedSearch $search, Carbon $now): bool
    {
        $hits = SavedSearchHit::query()
            ->where('saved_search_id', $search->id)
            ->whereNull('notified_at')
            ->orderByDesc('created_at')
            ->get();

        if ($hits->isEmpty()) {
            return false;
        }

        $listings = $this->listings($hits->take(self::MAX_PER_ALERT));

        SavedSearchHit::query()->where('saved_search_id', $search->id)->whereNull('notified_at')->update(['notified_at' => $now]);
        $search->forceFill(['last_notified_at' => $now])->saveQuietly();

        if ($listings->isEmpty() || $search->user === null) {
            return false;
        }

        $search->user->notify(new SavedSearchAlert($search, $listings->all(), $hits->count()));

        return true;
    }

    /**
     * Still-published listings of the hits, newest first.
     *
     * @param  Collection<int, SavedSearchHit>  $hits
     * @return Collection<int, Model>
     */
    private function listings(Collection $hits): Collection
    {
        return $hits->groupBy('listing_type')->flatMap(function (Collection $group, string $type): Collection {
            $class = Model::getActualClassNameForMorph($type);

            return $class::query()->with(['media', 'location'])->whereKey($group->pluck('listing_id')->all())->published()->get();
        })->sortByDesc('published_at')->values();
    }
}
