<x-layout.app :title="__('site.news.title')" :description="__('site.news.subtitle')">
    <x-page-header :title="__('site.news.title')" :subtitle="__('site.news.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.news')]]" />

    <div class="container-page pt-6 pb-[clamp(56px,6vw,96px)]">
        <nav aria-label="{{ __('home.search.fields.category') }}" class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-none">
            <x-pill :active="$category === null" :href="localized_route('news.index')">{{ __('site.filters.all_categories') }}</x-pill>
            @foreach ($categories as $item)
                <x-pill :active="$category?->is($item)" :href="localized_route('news.index', ['category' => $item->slug])">{{ $item->name }}</x-pill>
            @endforeach
        </nav>

        @if ($cards->isEmpty())
            <x-empty-state class="mt-8" icon="newspaper" />
        @else
            <div class="mt-6 grid grid-cols-[repeat(auto-fill,minmax(min(100%,320px),1fr))] gap-6">
                @foreach ($cards as $item)
                    <x-card.news :tag="$item['tag']" :date="$item['date']" :title="$item['title']" :url="$item['url']" :image="$item['image']" />
                @endforeach
            </div>
            <x-pagination :paginator="$articles" class="mt-8" />
        @endif
    </div>
</x-layout.app>
