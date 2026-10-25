@props([
    'total',
    'chips' => [],
    'sortOptions' => [],
    'views' => ['grid', 'list', 'map'],
    'view' => 'grid',
    'paginator',
    'hasMore' => false,
    'markers' => [],
    'activeCount' => 0,
    'searchPlaceholder' => null,
    // Comma-separated Livewire targets that change the results (dims them while loading).
    'targets' => 'q,sort,display,bounds',
])

{{-- Shared shell of every module list page (App\Livewire\Listings\ListingIndex). One filter panel:
     a sidebar from 1024px, a bottom sheet with a sticky "show N results" button below. --}}
<div x-data="listingPage" x-on:keydown.escape.window="closeFilters()" {{ $attributes->class(['container-page pt-6 pb-[clamp(56px,6vw,96px)]']) }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-[15px] text-secondary" aria-live="polite">
            {!! trans_choice('site.list.results', $total, ['count' => '<strong class="font-semibold text-navy">'.e(number_format($total, 0, '.', ' ')).'</strong>']) !!}
        </p>

        <div class="flex w-full items-center gap-2 md:w-auto md:gap-2.5">
            <button type="button" x-on:click="openFilters()" class="relative inline-flex min-h-11 shrink-0 items-center gap-2 rounded-button border border-line bg-white px-3.5 text-[14.5px] font-semibold text-navy hover:border-line-strong lg:hidden">
                <x-icon name="filter" :size="18" />
                {{ __('site.list.filters') }}
                @if ($activeCount > 0)
                    <span class="grid size-5 place-items-center rounded-full bg-coral-pressed text-[11.5px] font-bold text-white">{{ $activeCount }}</span>
                @endif
            </button>

            <label class="relative min-w-0 flex-1 md:flex-none">
                <span class="sr-only">{{ __('site.list.sort') }}</span>
                <select wire:model.live="sort" class="block min-h-11 w-full cursor-pointer truncate appearance-none rounded-button border border-line bg-white pr-10 pl-4 text-[14.5px] font-semibold text-navy hover:border-line-strong">
                    @foreach ($sortOptions as $value => $text)
                        <option value="{{ $value }}">{{ $text }}</option>
                    @endforeach
                </select>
                <x-icon name="chevron-down" :size="16" class="pointer-events-none absolute top-1/2 right-3.5 -translate-y-1/2 text-secondary" />
            </label>

            @if (count($views) > 1)
                <div role="group" aria-label="{{ __('site.list.view') }}" class="inline-flex shrink-0 rounded-button border border-line bg-white p-1">
                    @foreach ($views as $option)
                        <button type="button" wire:click="$set('display', '{{ $option }}')" aria-pressed="{{ $view === $option ? 'true' : 'false' }}"
                                class="inline-flex min-h-9 items-center gap-1.5 rounded-[9px] px-2.5 text-[13.5px] md:px-3 font-semibold text-secondary transition-colors duration-200 hover:text-navy aria-pressed:bg-navy aria-pressed:text-white">
                            <x-icon :name="['grid' => 'grid', 'list' => 'list', 'map' => 'map'][$option]" :size="16" />
                            <span class="max-xl:sr-only">{{ __('site.list.views.'.$option) }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($chips)
        <div class="mt-4 flex flex-wrap items-center gap-2">
            @foreach ($chips as $chip)
                <button type="button" wire:key="chip-{{ $chip['property'] }}-{{ $chip['value'] ?? '' }}"
                        wire:click="removeFilter(@js($chip['property']), @js($chip['value'] ?? null))"
                        class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-navy/7 pr-2.5 pl-3.5 text-[13.5px] font-semibold text-navy transition-colors duration-200 hover:bg-navy/12">
                    {{ $chip['label'] }}
                    <x-icon name="close" :size="14" />
                    <span class="sr-only">{{ __('site.list.remove_filter') }}</span>
                </button>
            @endforeach
            <button type="button" wire:click="resetFilters" class="min-h-9 px-2 text-[13.5px] font-semibold text-coral-pressed hover:underline">{{ __('site.list.clear_all') }}</button>
            <button type="button" wire:click="saveSearch" class="ml-auto inline-flex min-h-9 items-center gap-1.5 rounded-full border border-coral px-3.5 text-[13.5px] font-semibold text-navy transition-colors duration-200 hover:bg-coral-tint">
                <x-icon name="heart" :size="15" />
                {{ __('site.list.save_search') }}
            </button>
        </div>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-[296px_minmax(0,1fr)]">
        {{-- Backdrop of the mobile bottom sheet. --}}
        <div x-show="filtersOpen" x-transition.opacity x-on:click="closeFilters()" class="fixed inset-0 z-[70] bg-navy/55 lg:hidden" style="display: none"></div>

        <aside id="listing-filters"
               aria-label="{{ __('site.list.filters') }}"
               x-bind:class="filtersOpen ? 'max-lg:translate-y-0 max-lg:visible' : 'max-lg:translate-y-full max-lg:invisible'"
               class="max-lg:invisible max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-[71] max-lg:flex max-lg:max-h-[88dvh] max-lg:translate-y-full max-lg:flex-col max-lg:rounded-t-panel max-lg:bg-white max-lg:shadow-overlay max-lg:transition-[translate,visibility] max-lg:duration-300 lg:self-start lg:sticky lg:top-24">
            <div class="flex items-center justify-between gap-4 py-3 pr-3 pl-6 lg:hidden">
                <h2 class="font-serif text-xl font-semibold text-navy">{{ __('site.list.filters') }}</h2>
                <x-icon-button icon="close" :label="__('components.dialog.close')" variant="ghost" x-on:click="closeFilters()" />
            </div>
            <div class="overflow-y-auto overscroll-contain max-lg:px-6 max-lg:pb-4 lg:rounded-card lg:border lg:border-line lg:bg-white lg:p-5">
                <div class="relative mb-5">
                    <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-secondary" />
                    <input type="search" wire:model.live.debounce.500ms="q" placeholder="{{ $searchPlaceholder ?? __('site.list.search_placeholder') }}" aria-label="{{ __('site.list.search') }}"
                           class="block min-h-11 w-full rounded-field border border-line bg-white pr-3 pl-10 text-[15px] text-navy placeholder:text-secondary/70 hover:border-line-strong focus:border-navy">
                </div>
                {{ $filters }}
            </div>
            <div class="flex gap-3 border-t border-line px-6 py-3.5 pb-[max(14px,env(safe-area-inset-bottom))] lg:hidden">
                <x-button variant="ghost" wire:click="resetFilters">{{ __('site.list.reset') }}</x-button>
                <x-button class="flex-1" x-on:click="closeFilters()">
                    <span wire:loading.remove wire:target="{{ $targets }}">{{ trans_choice('site.list.show_results', $total, ['count' => number_format($total, 0, '.', ' ')]) }}</span>
                    <span wire:loading wire:target="{{ $targets }}">{{ __('site.list.loading') }}</span>
                </x-button>
            </div>
        </aside>

        <section aria-label="{{ __('site.list.results_label') }}" class="min-w-0" wire:loading.delay.class="opacity-60" wire:target="{{ $targets }}">
            @if ($view === 'map')
                <x-listing.map :markers="$markers" />
            @elseif ($total === 0)
                <x-empty-state icon="search" :title="__('site.list.empty_title')" :text="__('site.list.empty_text')">
                    <x-slot:action>
                        <x-button variant="secondary" wire:click="resetFilters">{{ __('site.list.clear_all') }}</x-button>
                    </x-slot:action>
                </x-empty-state>
            @else
                {{ $slot }}

                @if ($hasMore)
                    <div wire:intersect.margin.400px="loadMore" class="mt-8 flex justify-center">
                        <x-button variant="secondary" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                            <span wire:loading.remove wire:target="loadMore">{{ __('site.list.load_more') }}</span>
                            <span wire:loading wire:target="loadMore">{{ __('site.list.loading') }}</span>
                        </x-button>
                    </div>
                @endif

                <x-pagination :paginator="$paginator" class="mt-8" />
            @endif
        </section>
    </div>
</div>
