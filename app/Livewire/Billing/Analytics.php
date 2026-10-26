<?php

namespace App\Livewire\Billing;

use App\Models\User;
use App\Services\Account\OwnerListings;
use App\Services\DateFormatter;
use App\Support\Listings\PublicUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Listing analytics for business package holders: views, phone reveals, saves and messages per day,
 * and the best listings of the period.
 */
class Analytics extends Component
{
    public const array PERIODS = [7, 30, 90];

    public const array METRICS = ['views', 'phone_reveals', 'favorites', 'messages'];

    #[Url(except: 30)]
    public int $days = 30;

    public function updatedDays(): void
    {
        $this->days = in_array($this->days, self::PERIODS, true) ? $this->days : 30;
    }

    public function render(DateFormatter $dates): View
    {
        /** @var User $user */
        $user = auth()->user();
        $subscription = $user->activeBusinessSubscription();

        if ($subscription === null) {
            return view('livewire.billing.analytics', ['locked' => true]);
        }

        $from = Carbon::today()->subDays($this->days - 1);
        $daily = $this->stats($user, $from)
            ->selectRaw('date, sum(views) as views, sum(phone_reveals) as phone_reveals, sum(favorites) as favorites, sum(messages) as messages')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row): string => Carbon::parse($row->date)->toDateString());

        $series = [];

        for ($day = $from->copy(); $day->lessThanOrEqualTo(Carbon::today()); $day->addDay()) {
            $row = $daily->get($day->toDateString());
            $series[] = [
                'date' => $dates->date($day),
                'short' => $day->format('d.m'),
                'views' => (int) ($row->views ?? 0),
            ];
        }

        $totals = array_fill_keys(self::METRICS, 0);

        foreach ($daily as $row) {
            foreach (self::METRICS as $metric) {
                $totals[$metric] += (int) $row->{$metric};
            }
        }

        return view('livewire.billing.analytics', [
            'locked' => false,
            'until' => $dates->date($subscription->ends_at),
            'series' => $series,
            'max' => max(1, ...array_column($series, 'views')),
            'totals' => $totals,
            'top' => $this->top($user, $from),
            'periods' => collect(self::PERIODS)->mapWithKeys(fn (int $days): array => [$days => trans_choice('billing.days', $days, ['count' => $days])])->all(),
        ]);
    }

    private function stats(User $user, Carbon $from): QueryBuilder
    {
        return DB::table('daily_stats')
            ->where('date', '>=', $from->toDateString())
            ->where(function (QueryBuilder $query) use ($user): void {
                foreach (OwnerListings::TYPES as $type => $class) {
                    $table = (new $class)->getTable();
                    $query->orWhere(fn (QueryBuilder $query) => $query
                        ->where('statable_type', $type)
                        ->whereIn('statable_id', fn (QueryBuilder $ids) => $ids->select('id')->from($table)->where('user_id', $user->getKey())->whereNull('deleted_at')));
                }
            });
    }

    /**
     * @return list<array{title: string, module: string, url: ?string, views: int, phone_reveals: int, favorites: int, messages: int}>
     */
    private function top(User $user, Carbon $from): array
    {
        $rows = $this->stats($user, $from)
            ->selectRaw('statable_type, statable_id, sum(views) as views, sum(phone_reveals) as phone_reveals, sum(favorites) as favorites, sum(messages) as messages')
            ->groupBy('statable_type', 'statable_id')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $models = [];

        foreach ($rows->groupBy('statable_type') as $type => $group) {
            $class = OwnerListings::TYPES[$type] ?? null;

            if ($class !== null) {
                foreach ($class::query()->whereKey($group->pluck('statable_id')->all())->get() as $model) {
                    $models[$type.':'.$model->getKey()] = $model;
                }
            }
        }

        return $rows->map(function ($row) use ($models): ?array {
            /** @var Model|null $model */
            $model = $models[$row->statable_type.':'.$row->statable_id] ?? null;

            return $model === null ? null : [
                'title' => (string) $model->getAttribute('title'),
                'module' => __('account.types.'.$row->statable_type),
                'url' => method_exists($model, 'isPublished') && $model->isPublished() ? PublicUrl::for($model) : null,
                'views' => (int) $row->views,
                'phone_reveals' => (int) $row->phone_reveals,
                'favorites' => (int) $row->favorites,
                'messages' => (int) $row->messages,
            ];
        })->filter()->values()->all();
    }
}
