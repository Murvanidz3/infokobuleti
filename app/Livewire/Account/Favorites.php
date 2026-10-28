<?php

namespace App\Livewire\Account;

use App\Models\BusinessProfile;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\PriceFormatter;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\ListingTypes;
use App\Support\Listings\PublicUrl;
use App\Support\Listings\Visibility;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Everything the user saved, across modules. Items that are no longer public stay listed as
 * unavailable until the user removes them.
 */
class Favorites extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $type = '';

    #[Locked]
    public string $path = '';

    public function mount(): void
    {
        $this->path = url()->current();
        $this->type = in_array($this->type, ListingTypes::INTERACTIVE, true) ? $this->type : '';
    }

    public function updatedType(): void
    {
        $this->type = in_array($this->type, ListingTypes::INTERACTIVE, true) ? $this->type : '';
        $this->resetPage();
    }

    public function remove(int $id): void
    {
        Favorite::query()->where('user_id', $this->user()->getKey())->whereKey($id)->delete();

        $this->dispatch('toast', message: __('account.favorites.removed'), type: 'success');
    }

    public function render(CardPresenter $cards): View
    {
        $base = Favorite::query()->where('user_id', $this->user()->getKey());

        $counts = (clone $base)
            ->toBase()
            ->selectRaw('favoritable_type, count(*) as total')
            ->groupBy('favoritable_type')
            ->pluck('total', 'favoritable_type')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $paginator = (clone $base)
            ->when($this->type !== '', fn ($query) => $query->where('favoritable_type', $this->type))
            ->with(['favoritable' => fn (MorphTo $morph) => $morph->morphWith([
                Hotel::class => ['media', 'location'],
                Property::class => ['media', 'location'],
                Classified::class => ['media', 'location', 'category'],
                Vacancy::class => ['media', 'location', 'businessProfile'],
                Place::class => ['media', 'location'],
                Event::class => ['media', 'location'],
                BusinessProfile::class => ['media', 'location'],
            ])])
            ->latest('created_at')
            ->paginate(20)
            ->withPath($this->path)
            ->appends(array_filter(['type' => $this->type]));

        $items = collect($paginator->items())->map(fn (Favorite $favorite): array => $this->item($favorite, $cards))->all();

        return view('livewire.account.favorites', [
            'items' => $items,
            'paginator' => $paginator,
            'counts' => $counts,
            'total' => array_sum($counts),
            'types' => collect(ListingTypes::INTERACTIVE)
                ->filter(fn (string $type): bool => ($counts[$type] ?? 0) > 0)
                ->mapWithKeys(fn (string $type): array => [$type => __('account.types.'.$type)])
                ->all(),
        ]);
    }

    /**
     * @return array{id: int, type: string, title: string, subtitle: ?string, image: ?string, url: ?string, available: bool}
     */
    private function item(Favorite $favorite, CardPresenter $cards): array
    {
        $model = $favorite->favoritable;
        $available = $model instanceof Model && Visibility::isLive($model);

        return [
            'id' => (int) $favorite->getKey(),
            'type' => __('account.types.'.$favorite->favoritable_type),
            'title' => $model instanceof Model ? (string) ($model->getAttribute('title') ?? $model->getAttribute('name')) : __('account.favorites.deleted'),
            'subtitle' => $model instanceof Model && $available ? $this->subtitle($model, $cards) : null,
            'image' => $model instanceof Model && method_exists($model, 'coverUrl') ? $model->coverUrl('thumb') : null,
            'url' => $available ? PublicUrl::for($model) : null,
            'available' => $available,
        ];
    }

    private function subtitle(Model $model, CardPresenter $cards): string
    {
        $price = match (true) {
            $model instanceof Property => $cards->property($model)['price'],
            $model instanceof Classified => $cards->classifiedPrice($model),
            $model instanceof Vacancy => $cards->salary($model),
            $model instanceof Hotel && $model->price_from !== null => __('account.listings.price_from', ['price' => app(PriceFormatter::class)->format((float) $model->price_from)]),
            default => null,
        };

        return collect([$price, $model instanceof BusinessProfile ? null : $cards->locationText($model)])->filter()->implode(' · ');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
