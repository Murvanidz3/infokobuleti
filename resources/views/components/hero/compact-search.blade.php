<form action="#" method="get" role="search" {{ $attributes->class(['flex items-center gap-2 rounded-card border border-white/50 bg-white/94 py-1.5 pr-1.5 pl-4 shadow-overlay-compact backdrop-blur-[18px]']) }}>
    <x-icon name="search" :size="20" class="text-secondary" />
    <input type="search"
           name="q"
           aria-label="{{ __('home.search.label') }}"
           placeholder="{{ __('home.search.compact_placeholder') }}"
           class="h-11 min-w-0 flex-1 bg-transparent text-[15px] text-navy outline-none placeholder:text-secondary focus-visible:outline-none">
    <button type="submit" aria-label="{{ __('home.search.submit') }}" class="grid size-12 shrink-0 place-items-center rounded-button bg-coral-pressed text-white transition-colors duration-200 hover:bg-coral">
        <x-icon name="arrow-right" :size="20" :stroke="1.8" />
    </button>
</form>
