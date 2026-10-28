<x-section :background="$alt ? 'alt' : 'white'" id="news" aria-labelledby="news-title">
    <x-section-header id="news-title" :title="__('home.news.title')" :subtitle="__('home.news.subtitle')" :href="localized_route('news.index')" :link-label="__('home.news.all')" />
    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,320px),1fr))] gap-6">
        @foreach ($news as $item)
            <x-card.news :tag="$item['tag']" :date="$item['date']" :title="$item['title']" :url="$item['url']" :image="$item['image']" />
        @endforeach
    </div>
</x-section>
