<x-section :background="$alt ? 'alt' : 'white'" id="guide" aria-labelledby="guide-title">
    <x-section-header id="guide-title" :title="__('home.guide.title')" :subtitle="__('home.guide.subtitle')" :href="localized_route('guide.index')" :link-label="__('home.guide.all')" />
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr]">
        @foreach ($guide as $place)
            <x-card.guide
                :feature="$loop->first"
                :class="$loop->first
                    ? 'min-h-[420px] md:col-span-2 md:min-h-[440px] lg:col-span-1 lg:row-span-2 lg:min-h-[580px]'
                    : 'min-h-[240px] md:min-h-[260px] lg:min-h-[280px]'"
                :tag="$place['tag']" :title="$place['title']" :description="$place['description']" :meta="$place['meta']" :url="$place['url']" :image="$place['image']" />
        @endforeach
    </div>
</x-section>
