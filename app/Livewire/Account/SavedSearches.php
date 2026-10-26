<?php

namespace App\Livewire\Account;

use App\Enums\NotificationChannel;
use App\Enums\SearchFrequency;
use App\Enums\SiteFeature;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\DateFormatter;
use App\Support\Features;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Saved searches: rename, choose channels and frequency, pause, open or edit the filters, delete.
 */
class SavedSearches extends Component
{
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
            'availableChannels' => array_values(array_filter([
                NotificationChannel::Email->value,
                Features::active(SiteFeature::WebPush) ? NotificationChannel::Push->value : null,
                Features::active(SiteFeature::TelegramBot) ? NotificationChannel::Telegram->value : null,
            ])),
            'channelHints' => array_filter([
                NotificationChannel::Email->value => blank($this->user()->email) ? __('alerts.hints.no_email') : null,
                NotificationChannel::Push->value => ! $this->user()->pushSubscriptions()->exists() ? __('alerts.hints.no_push') : null,
                NotificationChannel::Telegram->value => blank($this->user()->telegram_chat_id) ? __('alerts.hints.no_telegram') : null,
            ]),
            'frequencyOptions' => SearchFrequency::options(),
        ]);
    }

    private function resultsUrl(SavedSearch $search, bool $edit = false): string
    {
        return $search->resultsUrl($edit);
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
