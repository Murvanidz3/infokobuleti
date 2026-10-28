{{-- Header search: a search button that opens an overlay with instant suggestions grouped by module
     (GET /search/suggest); Enter without a highlighted suggestion goes to the full results page. --}}
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" {{ $attributes }}>
    <button type="button" x-on:click="open = true; $nextTick(() => $refs.input.focus())" aria-label="{{ __('site.search.open') }}"
            class="grid size-11 place-items-center rounded-field border border-white/20 text-white transition-colors duration-200 hover:border-white/40">
        <x-icon name="search" :size="20" />
    </button>

    <template x-teleport="body">
        <div x-show="open" x-transition.opacity class="fixed inset-0 z-[80] flex items-start justify-center bg-navy/60 px-4 pt-[min(12vh,120px)] backdrop-blur-[2px]" x-on:click.self="open = false" style="display: none">
            <div x-data="siteSearch" class="w-full max-w-[640px] overflow-hidden rounded-panel bg-white shadow-overlay" role="dialog" aria-modal="true" aria-label="{{ __('site.search.title') }}">
                <form action="{{ localized_route('search') }}" method="get" role="search" x-on:submit="submit($event)" class="flex items-center gap-3 border-b border-line px-5">
                    <x-icon name="search" :size="20" class="shrink-0 text-secondary" />
                    <input x-ref="input" type="search" name="q" x-model="query" x-on:input="input()" autocomplete="off"
                           x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)"
                           placeholder="{{ __('site.search.placeholder') }}" aria-label="{{ __('site.search.title') }}"
                           role="combobox" aria-autocomplete="list" aria-controls="search-suggestions" x-bind:aria-expanded="groups.length > 0"
                           class="h-16 min-w-0 flex-1 bg-transparent text-[16.5px] text-navy outline-none placeholder:text-secondary focus-visible:outline-none">
                    <span x-show="loading" class="size-4 animate-spin rounded-full border-2 border-line border-t-coral"></span>
                    <x-icon-button icon="close" :label="__('components.dialog.close')" variant="ghost" x-on:click="open = false" />
                </form>
                <div id="search-suggestions" role="listbox" class="max-h-[60vh] overflow-y-auto">
                    <template x-for="(group, groupIndex) in groups" x-bind:key="group.module">
                        <div class="border-b border-line px-2 py-2 last:border-b-0">
                            <p class="px-3 pt-1 pb-1.5 text-[12px] font-semibold tracking-wide text-secondary uppercase" x-text="group.label"></p>
                            <template x-for="item in group.items" x-bind:key="item.url">
                                <a x-bind:href="item.url" role="option" x-bind:aria-selected="flat.indexOf(item) === active"
                                   class="flex items-center gap-3 rounded-tile px-3 py-2.5 aria-selected:bg-surface-alt hover:bg-surface-alt">
                                    <span class="size-10 shrink-0 overflow-hidden rounded-chip bg-line">
                                        <img x-show="item.image" x-bind:src="item.image" alt="" class="size-full object-cover">
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[15px] font-semibold text-navy" x-text="item.title"></span>
                                        <span class="block truncate text-[13px] text-secondary" x-text="item.meta"></span>
                                    </span>
                                </a>
                            </template>
                            <a x-bind:href="group.all" class="mx-3 my-1 inline-block text-[13px] font-semibold text-coral-pressed hover:underline">{{ __('site.search.see_all') }} →</a>
                        </div>
                    </template>
                    <p x-show="!loading && query.trim().length >= 2 && groups.length === 0" class="px-6 py-8 text-center text-[15px] text-secondary">{{ __('site.search.nothing') }}</p>
                </div>
            </div>
        </div>
    </template>
</div>
