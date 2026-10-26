<?php

namespace App\Livewire\Account;

use App\Enums\ListingModule;
use App\Enums\NotificationChannel;
use App\Enums\SearchFrequency;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\DateFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Saved searches: rename, choose channels and frequency, pause, open or edit the filters, delete.
 */
class SavedSearches extends Component
{
    /** Channels that already deliver alerts; push and Telegram arrive with Phase 9. */
    public const array AVAILABLE_CHANNELS = ['email'];

    public ?int $editing = null;

    public string $name = '';

    /** @var list<string> */
    public array $channels = [];

    public string $frequency = 'daily';

    public function edit(int $id): void
    {
        $search = $this->find($id);

        $this->editing = $search->id;
        $this->name = (string) $search->name;
        $this->channels = array_values(array_intersect($search->channels ?? [], NotificationChannel::values()));
        $this->frequency = $search->frequency->value;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset('editing', 'name', 'channels', 'frequency');
        $this->resetValidation();
    }

    public function save(): void
    {
        if ($this->editing === null) {
            return;
        }

        $search = $this->find($this->editing);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'channels' => ['array'],
            'channels.*' => [Rule::enum(NotificationChannel::class)],
            'frequency' => ['required', Rule::enum(SearchFrequency::class)],
        ], [], [
            'name' => __('account.searches.name'),
            'channels' => __('account.searches.channels'),
            'frequency' => __('account.searches.frequency'),
        ]);

        $search->update([
            'name' => trim($data['name']),
            'channels' => array_values(array_unique($data['channels'])),
            'frequency' => $data['frequency'],
        ]);

        $this->cancel();
        $this->dispatch('toast', message: __('account.searches.saved'), type: 'success');
    }

    public function toggle(int $id): void
    {
        $search = $this->find($id);
        $search->update(['is_active' => ! $search->is_active]);
    }

    public function delete(int $id): void
    {
        $this->find($id)->delete();

        if ($this->editing === $id) {
            $this->cancel();
        }

        $this->dispatch('toast', message: __('account.searches.deleted'), type: 'success');
    }

    public function render(DateFormatter $dates): View
    {
        $searches = SavedSearch::query()
            ->where('user_id', $this->user()->getKey())
            ->latest()
            ->get()
            ->map(fn (SavedSearch $search): array => [
                'id' => $search->id,
                'name' => $search->name ?: $search->module->label(),
                'module' => $search->module->label(),
                'filters' => count($search->filters ?? []),
                'channels' => collect($search->channels ?? [])->map(fn (string $channel): string => NotificationChannel::tryFrom($channel)?->label() ?? $channel)->implode(', '),
                'frequency' => $search->frequency->label(),
                'active' => $search->is_active,
                'created' => $dates->date($search->created_at ?? now()),
                'url' => $this->resultsUrl($search),
                'edit_url' => $this->resultsUrl($search, edit: true),
            ]);

        return view('livewire.account.saved-searches', [
            'searches' => $searches,
            'channelOptions' => NotificationChannel::options(),
            'frequencyOptions' => SearchFrequency::options(),
        ]);
    }

    /**
     * The module's list page with the saved filters; with $edit, "save" there updates this search.
     */
    private function resultsUrl(SavedSearch $search, bool $edit = false): string
    {
        $route = match ($search->module) {
            ListingModule::Hotels => 'hotels.index',
            ListingModule::Properties => 'real-estate.index',
            ListingModule::Classifieds => 'classifieds.index',
            ListingModule::Vacancies => 'jobs.index',
        };

        $query = http_build_query(array_filter([...($search->filters ?? []), 'saved' => $edit ? $search->id : null], fn (mixed $value): bool => $value !== null));

        return localized_route($route).($query !== '' ? '?'.$query : '');
    }

    private function find(int $id): SavedSearch
    {
        return SavedSearch::query()->where('user_id', $this->user()->getKey())->findOrFail($id);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
