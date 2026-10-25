<x-layout.app :title="$query !== '' ? __('site.search.results_for', ['query' => $query]) : __('site.search.title')">
    <div class="border-b border-line bg-surface-alt">
        <div class="container-page py-[clamp(24px,3.5vw,44px)]">
            <h1 class="font-serif text-[clamp(26px,3vw,38px)] font-semibold text-navy">{{ __('site.search.title') }}</h1>
            <form action="{{ localized_route('search') }}" method="get" role="search" class="mt-5 flex max-w-[720px] items-center gap-2 rounded-card border border-line bg-white py-1.5 pr-1.5 pl-4 focus-within:border-navy">
                <x-icon name="search" :size="20" class="shrink-0 text-secondary" />
                <input type="search" name="q" value="{{ $query }}" placeholder="{{ __('site.search.placeholder') }}" aria-label="{{ __('site.search.title') }}" class="h-11 min-w-0 flex-1 bg-transparent text-[16px] text-navy outline-none focus-visible:outline-none">
                <x-button type="submit">{{ __('home.search.submit') }}</x-button>
            </form>
        </div>
    </div>

    <div class="container-page py-8 pb-[clamp(56px,6vw,96px)]">
        @if ($query === '' || mb_strlen($query) < 2)
            <x-empty-state icon="search" :title="__('site.search.start_title')" :text="__('site.search.start_text')" />
        @elseif (! $groups)
            <x-empty-state icon="search" :title="__('site.search.nothing')" :text="__('site.search.nothing_text')" />
        @else
            <div class="flex flex-col gap-10">
                @foreach ($groups as $group)
                    <section aria-labelledby="group-{{ $group['module'] }}">
                        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
                            <h2 id="group-{{ $group['module'] }}" class="font-serif text-[22px] font-semibold text-navy">{{ $group['label'] }} <span class="text-[15px] font-normal text-secondary">{{ $group['total'] }}</span></h2>
                            @if ($group['total'] > count($group['items']))
                                <a href="{{ $group['all'] }}" class="text-[14px] font-semibold text-coral-pressed hover:underline">{{ __('site.search.see_all') }} →</a>
                            @endif
                        </div>
                        <ul class="grid gap-3 xs:grid-cols-2 lg:grid-cols-3">
                            @foreach ($group['items'] as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="flex h-full items-center gap-3.5 rounded-tile border border-line bg-white p-3 transition-colors hover:border-line-strong">
                                        <span class="size-16 shrink-0 overflow-hidden rounded-chip bg-line"><x-image :src="$item['image']" alt="" /></span>
                                        <span class="min-w-0">
                                            <span class="line-clamp-2 text-[15px] font-semibold text-navy">{{ $item['title'] }}</span>
                                            <span class="mt-0.5 block truncate text-[13px] text-secondary">{{ $item['meta'] }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layout.app>
