<div class="flex flex-col gap-3">
    @forelse ($searches as $search)
        <article wire:key="search-{{ $search['id'] }}" class="rounded-card border border-line bg-white p-4 shadow-card md:p-5">
            <div class="flex flex-wrap items-start gap-4">
                <span @class(['grid size-11 shrink-0 place-items-center rounded-full', 'bg-navy text-white' => $search['active'], 'bg-surface-alt text-secondary' => ! $search['active']])>
                    <x-icon name="search" :size="20" />
                </span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-[16px] leading-snug font-semibold text-navy">{{ $search['name'] }}</h3>
                    <p class="mt-1 text-[13.5px] text-secondary">
                        {{ $search['module'] }} · {{ trans_choice('account.searches.filters_count', $search['filters'], ['count' => $search['filters']]) }} · {{ __('account.searches.created', ['date' => $search['created']]) }}
                    </p>
                    <p class="mt-1 text-[13.5px] text-secondary">
                        @if ($search['active'])
                            {{ $search['frequency'] }}@if ($search['channels'] !== '') · {{ $search['channels'] }}@endif
                        @else
                            {{ __('account.searches.paused') }}
                        @endif
                    </p>
                </div>
                <label class="relative inline-flex min-h-11 cursor-pointer items-center gap-2.5 text-[13.5px] font-semibold text-navy">
                    <input type="checkbox" class="peer sr-only" wire:click="toggle({{ $search['id'] }})" @checked($search['active'])>
                    <span class="relative h-6 w-11 rounded-full bg-line-strong transition-colors peer-checked:bg-navy peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5"></span>
                    <span>{{ __('account.searches.alerts') }}</span>
                </label>
            </div>

            @if ($editing === $search['id'])
                <form wire:submit="save" class="mt-4 flex flex-col gap-4 rounded-tile bg-surface-alt p-4 md:p-5">
                    <x-form.field :label="__('account.searches.name')" for="search-name-{{ $search['id'] }}" :error="$errors->first('name')">
                        <x-form.input name="name" id="search-name-{{ $search['id'] }}" wire:model="name" maxlength="120" />
                    </x-form.field>

                    <fieldset>
                        <legend class="mb-2 text-[14px] font-semibold text-navy">{{ __('account.searches.channels') }}</legend>
                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                            @foreach ($channelOptions as $value => $label)
                                @php($available = in_array($value, \App\Livewire\Account\SavedSearches::AVAILABLE_CHANNELS, true))
                                <label @class(['inline-flex min-h-11 items-center gap-2.5 text-[14.5px] text-navy', 'opacity-60' => ! $available])>
                                    <input type="checkbox" value="{{ $value }}" wire:model="channels" @disabled(! $available) class="size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral">
                                    {{ $label }}
                                    @unless ($available)<span class="text-[12.5px] text-secondary">({{ __('account.searches.soon') }})</span>@endunless
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="mb-2 text-[14px] font-semibold text-navy">{{ __('account.searches.frequency') }}</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($frequencyOptions as $value => $label)
                                <label class="relative">
                                    <input type="radio" value="{{ $value }}" wire:model="frequency" class="peer sr-only">
                                    <span class="inline-flex min-h-11 cursor-pointer items-center rounded-full border border-line bg-white px-[18px] text-sm font-semibold text-secondary transition-colors peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="flex flex-wrap gap-2">
                        <x-button type="submit" size="sm">{{ __('account.common.save') }}</x-button>
                        <x-button size="sm" variant="ghost" wire:click="cancel">{{ __('account.common.cancel') }}</x-button>
                    </div>
                </form>
            @endif

            <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                <x-button size="sm" icon="search" :href="$search['url']">{{ __('account.searches.open') }}</x-button>
                <x-button size="sm" variant="secondary" icon="filter" :href="$search['edit_url']">{{ __('account.searches.edit_filters') }}</x-button>
                @if ($editing !== $search['id'])
                    <x-button size="sm" variant="ghost" icon="mail" wire:click="edit({{ $search['id'] }})">{{ __('account.searches.settings') }}</x-button>
                @endif
                <x-button size="sm" variant="ghost" icon="close" class="ml-auto" wire:click="delete({{ $search['id'] }})" wire:confirm="{{ __('account.searches.confirm_delete') }}">{{ __('account.common.delete') }}</x-button>
            </div>
        </article>
    @empty
        <x-empty-state icon="search" :title="__('account.searches.empty_title')" :text="__('account.searches.empty_text')">
            <x-slot:action>
                <x-button variant="secondary" :href="localized_route('real-estate.index')">{{ __('account.searches.browse') }}</x-button>
            </x-slot:action>
        </x-empty-state>
    @endforelse
</div>
