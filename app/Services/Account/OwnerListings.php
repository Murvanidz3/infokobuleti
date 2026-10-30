<?php

namespace App\Services\Account;

use App\Contracts\Listing;
use App\Enums\ListingGroup;
use App\Enums\ListingStatus;
use App\Enums\PromotionType;
use App\Models\Classified;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\DateFormatter;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\PublicUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The signed-in owner's listings across the four modules: counts per group, filtered queries and
 * display rows with their statistics and the actions the owner may take.
 */
class OwnerListings
{
    /**
     * Morph alias => model class, in the order the account shows the modules.
     *
     * @var array<string, class-string<Model&Listing>>
     */
    public const array TYPES = [
        'property' => Property::class,
        'project' => Project::class,
        'classified' => Classified::class,
        'vacancy' => Vacancy::class,
    ];

    /** Days before expiry when the account starts warning about it. */
    public const int EXPIRY_WARNING_DAYS = 3;

    public function __construct(
        private CardPresenter $cards,
        private DateFormatter $dates,
    ) {}

    /**
     * @return Builder<Model>
     */
    public function query(User $user, string $type, ?ListingGroup $group = null): Builder
    {
        $class = self::TYPES[$type] ?? abort(404);

        /** @var Builder<Model> $query */
        $query = $class::query()
            ->where('user_id', $user->getKey())
            ->where('status', '!=', ListingStatus::Archived->value)
            ->with(in_array($type, ['classified', 'vacancy'], true) ? ['media', 'location', 'category'] : ['media', 'location']);

        if ($group !== null) {
            $this->applyGroup($query, $group);
        }

        return $query;
    }

    /**
     * One of the user's listings, or a 404 (other people's listings are never found).
     * The account list hides archived listings; the editor passes $withArchived so an owner
     * can open one and send it back to moderation.
     */
    public function find(User $user, string $type, int $id, bool $withArchived = false): Model&Listing
    {
        if (! $withArchived) {
            /** @var Model&Listing */
            return $this->query($user, $type)->whereKey($id)->firstOrFail();
        }

        $class = self::TYPES[$type] ?? abort(404);

        /** @var Model&Listing */
        return $class::query()
            ->where('user_id', $user->getKey())
            ->with(in_array($type, ['classified', 'vacancy'], true) ? ['media', 'location', 'category'] : ['media', 'location'])
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * Number of listings per module and group, plus "all" totals.
     *
     * @return array<string, array<string, int>> type|"all" => group|"total" => count
     */
    public function counts(User $user): array
    {
        $counts = ['all' => array_fill_keys([...ListingGroup::values(), 'total'], 0)];

        foreach (self::TYPES as $type => $class) {
            $now = now()->toDateTimeString();
            $row = (array) $class::query()
                ->toBase()
                ->where('user_id', $user->getKey())
                ->whereNull('deleted_at')
                ->where('status', '!=', ListingStatus::Archived->value)
                ->selectRaw('count(*) as total')
                ->selectRaw("count(*) filter (where status = 'published' and (expires_at is null or expires_at > ?)) as active", [$now])
                ->selectRaw("count(*) filter (where status = 'pending') as pending")
                ->selectRaw("count(*) filter (where status in ('draft', 'rejected')) as attention")
                ->selectRaw("count(*) filter (where status = 'paused') as paused")
                ->selectRaw("count(*) filter (where status = 'expired' or (status = 'published' and expires_at <= ?)) as expired", [$now])
                ->first();

            foreach ([...ListingGroup::values(), 'total'] as $key) {
                $counts[$type][$key] = (int) ($row[$key] ?? 0);
                $counts['all'][$key] += $counts[$type][$key];
            }
        }

        return $counts;
    }

    /**
     * All-time totals of the owner's statistics across every listing.
     *
     * @return array{views: int, phone_reveals: int, favorites: int, messages: int}
     */
    public function totals(User $user): array
    {
        $stats = DB::table('daily_stats')
            ->where(function (QueryBuilder $query) use ($user): void {
                foreach (self::TYPES as $type => $class) {
                    $table = (new $class)->getTable();
                    $query->orWhere(fn (QueryBuilder $query) => $query
                        ->where('statable_type', $type)
                        ->whereIn('statable_id', fn (QueryBuilder $ids) => $ids->select('id')->from($table)->where('user_id', $user->getKey())->whereNull('deleted_at')));
                }
            })
            ->selectRaw('coalesce(sum(views), 0) as views, coalesce(sum(phone_reveals), 0) as phone_reveals, coalesce(sum(favorites), 0) as favorites, coalesce(sum(messages), 0) as messages')
            ->first();

        return [
            'views' => (int) ($stats->views ?? 0),
            'phone_reveals' => (int) ($stats->phone_reveals ?? 0),
            'favorites' => (int) ($stats->favorites ?? 0),
            'messages' => (int) ($stats->messages ?? 0),
        ];
    }

    /**
     * Listings that need the owner now: rejected, drafts and those about to expire or expired.
     *
     * @return list<array<string, mixed>>
     */
    public function needingAttention(User $user, int $limit = 5): array
    {
        $listings = new Collection;

        foreach (array_keys(self::TYPES) as $type) {
            $listings = $listings->merge(
                $this->query($user, $type)
                    ->where(fn (Builder $query) => $query
                        ->whereIn('status', [ListingStatus::Rejected->value, ListingStatus::Draft->value, ListingStatus::Expired->value])
                        ->orWhere(fn (Builder $query) => $query
                            ->whereIn('status', [ListingStatus::Published->value, ListingStatus::Paused->value])
                            ->where('expires_at', '<=', now()->addDays(self::EXPIRY_WARNING_DAYS))))
                    ->latest('updated_at')
                    ->limit($limit)
                    ->get(),
            );
        }

        return $this->rows($listings->sortByDesc('updated_at')->take($limit)->values());
    }

    /**
     * The most recently changed listings across modules.
     *
     * @return list<array<string, mixed>>
     */
    public function recent(User $user, int $limit = 5): array
    {
        $listings = new Collection;

        foreach (array_keys(self::TYPES) as $type) {
            $listings = $listings->merge($this->query($user, $type)->latest('updated_at')->limit($limit)->get());
        }

        return $this->rows($listings->sortByDesc('updated_at')->take($limit)->values());
    }

    /**
     * Display rows for a page of listings (statistics loaded with one query per module).
     *
     * @param  iterable<Model>  $listings
     * @return list<array<string, mixed>>
     */
    public function rows(iterable $listings): array
    {
        $listings = collect($listings);
        $stats = $this->statsFor($listings);

        return $listings->map(function (Model $listing) use ($stats): array {
            /** @var Model&Listing $listing */
            $key = $listing->getMorphClass().':'.$listing->getKey();
            $status = $listing->listingStatus();
            $expiresAt = $listing->getAttribute('expires_at');
            $group = ListingGroup::of($status, $expiresAt);
            $user = auth()->user();

            return [
                'key' => $key,
                'type' => $listing->getMorphClass(),
                'id' => (int) $listing->getKey(),
                'module' => __('account.types.'.$listing->getMorphClass()),
                'title' => (string) $listing->getAttribute('title'),
                'image' => method_exists($listing, 'coverUrl') ? $listing->coverUrl('thumb') : null,
                'price' => $this->priceText($listing),
                'location' => $this->cards->locationText($listing),
                'status' => $group?->label() ?? $status->label(),
                'group' => $group?->value,
                'icon' => $group?->icon() ?? 'info',
                'reason' => $status === ListingStatus::Rejected ? $listing->getAttribute('rejection_reason') : null,
                'expires' => $this->expiryText($expiresAt, $status),
                'expiring' => $expiresAt instanceof Carbon && $status === ListingStatus::Published && $expiresAt->isFuture() && $expiresAt->lessThanOrEqualTo(now()->addDays(self::EXPIRY_WARNING_DAYS)),
                'updated' => $this->dates->relative($listing->getAttribute('updated_at') ?? now()),
                'url' => (string) PublicUrl::for($listing),
                'views' => (int) $listing->getAttribute('views_count'),
                'phone_reveals' => $stats[$key]['phone_reveals'] ?? 0,
                'favorites' => $stats[$key]['favorites'] ?? 0,
                'promotion' => match ((int) $listing->getAttribute('promotion_level')) {
                    2 => PromotionType::SuperVip->label(),
                    1 => PromotionType::Vip->label(),
                    default => null,
                },
                'promoted_until' => (int) $listing->getAttribute('promotion_level') > 0 && $listing->getAttribute('promoted_until') instanceof Carbon
                    ? __('billing.until', ['date' => $this->dates->date($listing->getAttribute('promoted_until'))])
                    : null,
                'can' => [
                    'promote' => $user !== null && (int) $listing->getAttribute('user_id') === (int) $user->getKey() && $status === ListingStatus::Published && ! ($expiresAt instanceof Carbon && $expiresAt->isPast()),
                    'edit' => $user !== null && Gate::forUser($user)->allows('update', $listing),
                    'pause' => $user !== null && Gate::forUser($user)->allows('pause', $listing),
                    'resume' => $user !== null && Gate::forUser($user)->allows('resume', $listing),
                    'renew' => $user !== null && Gate::forUser($user)->allows('renew', $listing),
                    'delete' => $user !== null && Gate::forUser($user)->allows('delete', $listing),
                ],
            ];
        })->values()->all();
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applyGroup(Builder $query, ListingGroup $group): void
    {
        match ($group) {
            ListingGroup::Active => $query->where('status', ListingStatus::Published->value)
                ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now())),
            ListingGroup::Pending => $query->where('status', ListingStatus::Pending->value),
            ListingGroup::Attention => $query->whereIn('status', [ListingStatus::Draft->value, ListingStatus::Rejected->value]),
            ListingGroup::Paused => $query->where('status', ListingStatus::Paused->value),
            ListingGroup::Expired => $query->where(fn (Builder $query) => $query
                ->where('status', ListingStatus::Expired->value)
                ->orWhere(fn (Builder $query) => $query->where('status', ListingStatus::Published->value)->where('expires_at', '<=', now()))),
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Model>  $listings
     * @return array<string, array{phone_reveals: int, favorites: int}>
     */
    private function statsFor(\Illuminate\Support\Collection $listings): array
    {
        if ($listings->isEmpty()) {
            return [];
        }

        $rows = DB::table('daily_stats')
            ->where(function (QueryBuilder $query) use ($listings): void {
                foreach ($listings->groupBy(fn (Model $listing): string => $listing->getMorphClass()) as $type => $items) {
                    $query->orWhere(fn (QueryBuilder $query) => $query->where('statable_type', $type)->whereIn('statable_id', $items->map(fn (Model $listing): mixed => $listing->getKey())->all()));
                }
            })
            ->groupBy('statable_type', 'statable_id')
            ->select('statable_type', 'statable_id')
            ->selectRaw('sum(phone_reveals) as phone_reveals, sum(favorites) as favorites')
            ->get();

        $stats = [];

        foreach ($rows as $row) {
            $stats[$row->statable_type.':'.$row->statable_id] = ['phone_reveals' => (int) $row->phone_reveals, 'favorites' => (int) $row->favorites];
        }

        return $stats;
    }

    private function priceText(Model $listing): ?string
    {
        return match (true) {
            $listing instanceof Property => $this->cards->property($listing)['price'],
            $listing instanceof Project => $this->cards->project($listing)['price'],
            $listing instanceof Classified => $this->cards->classifiedPrice($listing),
            $listing instanceof Vacancy => $this->cards->salary($listing),
            default => null,
        };
    }

    private function expiryText(mixed $expiresAt, ListingStatus $status): ?string
    {
        if (! $expiresAt instanceof Carbon || ! in_array($status, [ListingStatus::Published, ListingStatus::Paused, ListingStatus::Expired], true)) {
            return null;
        }

        return $expiresAt->isPast()
            ? __('account.listings.expired_on', ['date' => $this->dates->date($expiresAt)])
            : __('account.listings.expires_on', ['date' => $this->dates->date($expiresAt)]);
    }
}
