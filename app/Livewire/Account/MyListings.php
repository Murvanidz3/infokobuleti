<?php

namespace App\Livewire\Account;

use App\Actions\Listings\DeleteListing;
use App\Actions\Listings\PauseListing;
use App\Actions\Listings\RenewListing;
use App\Actions\Listings\ResumeListing;
use App\Enums\ListingGroup;
use App\Exceptions\InvalidStatusTransition;
use App\Models\User;
use App\Services\Account\OwnerListings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The owner's listings of one module, filtered by group, with pause / resume / renew / delete.
 */
class MyListings extends Component
{
    use WithPagination;

    public const int PER_PAGE = 12;

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $group = '';

    /** Page URL for the pagination links (Livewire requests come from another path). */
    #[Locked]
    public string $path = '';

    public function mount(OwnerListings $listings): void
    {
        $this->path = url()->current();

        if (! array_key_exists($this->type, OwnerListings::TYPES)) {
            $counts = $listings->counts($this->user());
            $this->type = collect(array_keys(OwnerListings::TYPES))->first(fn (string $type): bool => $counts[$type]['total'] > 0) ?? 'property';
        }

        if (ListingGroup::tryFrom($this->group) === null) {
            $this->group = '';
        }
    }

    public function updatedType(): void
    {
        $this->type = array_key_exists($this->type, OwnerListings::TYPES) ? $this->type : 'property';
        $this->resetPage();
    }

    public function updatedGroup(): void
    {
        $this->group = ListingGroup::tryFrom($this->group)->value ?? '';
        $this->resetPage();
    }

    public function pause(OwnerListings $listings, PauseListing $action, string $type, int $id): void
    {
        $this->run($listings, $type, $id, 'pause', fn ($listing) => $action->handle($this->user(), $listing), __('account.listings.paused'));
    }

    public function resume(OwnerListings $listings, ResumeListing $action, string $type, int $id): void
    {
        $this->run($listings, $type, $id, 'resume', fn ($listing) => $action->handle($this->user(), $listing), __('account.listings.resumed'));
    }

    public function renew(OwnerListings $listings, RenewListing $action, string $type, int $id): void
    {
        $this->run($listings, $type, $id, 'renew', fn ($listing) => $action->handle($this->user(), $listing), __('account.listings.renewed'));
    }

    public function delete(OwnerListings $listings, DeleteListing $action, string $type, int $id): void
    {
        $this->run($listings, $type, $id, 'delete', fn ($listing) => $action->handle($this->user(), $listing), __('account.listings.deleted'));
    }

    public function render(OwnerListings $listings): View
    {
        $user = $this->user();
        $counts = $listings->counts($user);

        $paginator = $listings->query($user, $this->type, ListingGroup::tryFrom($this->group))
            ->latest('updated_at')
            ->paginate(self::PER_PAGE)
            ->withPath($this->path)
            ->appends(array_filter(['type' => $this->type, 'group' => $this->group]));

        return view('livewire.account.my-listings', [
            'rows' => $listings->rows($paginator->items()),
            'counts' => $counts,
            'paginator' => $paginator,
            'groups' => ListingGroup::options(),
            'types' => collect(array_keys(OwnerListings::TYPES))->mapWithKeys(fn (string $type): array => [$type => __('account.types.'.$type)])->all(),
        ]);
    }

    /**
     * @param  callable(mixed): void  $callback
     */
    protected function run(OwnerListings $listings, string $type, int $id, string $ability, callable $callback, string $message): void
    {
        $listing = $listings->find($this->user(), $type, $id);

        if (! Gate::forUser($this->user())->allows($ability, $listing)) {
            $this->dispatch('toast', message: __('account.listings.not_allowed'), type: 'error');

            return;
        }

        try {
            $callback($listing);
        } catch (InvalidStatusTransition) {
            $this->dispatch('toast', message: __('account.listings.not_allowed'), type: 'error');

            return;
        }

        $this->dispatch('toast', message: $message, type: 'success');
        $this->dispatch('listings-changed');
    }

    protected function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
