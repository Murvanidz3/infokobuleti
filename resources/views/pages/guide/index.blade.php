<x-layout.app :title="__('site.guide.title')" :description="__('site.guide.subtitle')" :seo="['page' => 'guide']">
    <x-page-header :title="__('site.guide.title')" :subtitle="__('site.guide.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.guide')]]">
        <x-button variant="secondary" :href="localized_route('events.index')" icon="calendar">{{ __('nav.events') }}</x-button>
    </x-page-header>

    <div class="container-page pt-6 pb-[clamp(56px,6vw,96px)]">
        <nav aria-label="{{ __('site.filters.type') }}" class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-none">
            <x-pill :active="$type === null" :href="localized_route('guide.index')">{{ __('site.guide.all') }}</x-pill>
            @foreach ($types as $case)
                <x-pill :active="$type === $case" :href="localized_route('guide.index', ['type' => $case->value])">{{ $case->getLabel() }} <span class="opacity-60">{{ $counts[$case->value] ?? 0 }}</span></x-pill>
            @endforeach
        </nav>

        @if ($cards->isEmpty())
            <x-empty-state class="mt-8" icon="compass" />
        @else
            <div class="mt-6 grid grid-cols-1 gap-5 xs:grid-cols-2 lg:grid-cols-3">
                @foreach ($cards as $place)
                    <x-card.guide :feature="$loop->first && $places->onFirstPage()" :class="$loop->first && $places->onFirstPage() ? 'min-h-[360px] xs:col-span-2 lg:row-span-2 lg:min-h-[540px]' : 'min-h-[260px]'"
                                  :tag="$place['tag']" :title="$place['title']" :description="$loop->first ? $place['description'] : null" :meta="$place['meta']" :url="$place['url']" :image="$place['image']" />
                @endforeach
            </div>
            <x-pagination :paginator="$places" class="mt-8" />
        @endif

        @if ($markers)
            <x-section-header size="sm" :title="__('site.guide.on_map')" class="mt-14" />
            <x-listing.map :markers="$markers" :search-area="false" height="h-[440px]" />
        @endif

        @if ($events->isNotEmpty())
            <x-section-header size="sm" :title="__('site.events.upcoming')" :href="localized_route('events.index')" class="mt-14" />
            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-5">
                @foreach ($events as $event)
                    <x-card.event :tag="$event['tag']" :title="$event['title']" :day="$event['day']" :month="$event['month']" :when="$event['when']" :venue="$event['venue']" :price="$event['price']" :url="$event['url']" :image="$event['image']" />
                @endforeach
            </div>
        @endif
    </div>
</x-layout.app>
