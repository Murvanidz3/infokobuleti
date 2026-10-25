<x-layout.app :title="$article->title" :description="$description">
    <x-slot:head>
        <meta property="og:type" content="article">
        @if ($image)
            <meta property="og:image" content="{{ $image }}">
        @endif
    </x-slot:head>

    @if ($preview)
        <x-detail.preview-banner :status="$article->status->getLabel()" />
    @endif

    <article class="container-page max-w-[840px] pt-5 pb-[clamp(56px,6vw,96px)]">
        <x-breadcrumbs :items="[
            ['label' => __('nav.home'), 'url' => localized_route('home')],
            ['label' => __('nav.news'), 'url' => localized_route('news.index')],
            ['label' => $article->title],
        ]" />

        <header class="mt-6">
            @if ($article->category)
                <a href="{{ localized_route('news.index', ['category' => $article->category->slug]) }}" class="text-[13px] font-semibold tracking-wide text-coral-pressed uppercase hover:underline">{{ $article->category->name }}</a>
            @endif
            <h1 class="mt-2 font-serif text-[clamp(28px,3.4vw,44px)] leading-[1.2] font-semibold text-balance text-navy">{{ $article->title }}</h1>
            @if (filled($article->excerpt))
                <p class="mt-4 text-[clamp(17px,1.3vw,19px)] leading-[1.65] text-secondary">{{ $article->excerpt }}</p>
            @endif
            <p class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-[14px] text-secondary">
                <time datetime="{{ $article->published_at?->toDateString() }}">{{ $date }}</time>
                <span>{{ trans_choice('site.news.reading_time', $readingMinutes, ['count' => $readingMinutes]) }}</span>
                <span x-data="shareButton(@js($article->title), @js(__('site.share.copied')))"><button type="button" x-on:click="share()" class="inline-flex items-center gap-1.5 font-semibold text-navy hover:text-coral-pressed"><x-icon name="share" :size="15" />{{ __('site.share.button') }}</button></span>
            </p>
        </header>

        @if ($image)
            <figure class="mt-7 overflow-hidden rounded-card bg-line">
                <img src="{{ $image }}" alt="{{ $article->title }}" fetchpriority="high" class="aspect-16/9 w-full object-cover">
            </figure>
        @endif

        <div class="prose-site mt-8">{!! $body !!}</div>
    </article>

    @if ($related->isNotEmpty())
        <x-section background="alt" aria-labelledby="related-title">
            <x-section-header id="related-title" :title="__('site.news.related')" :href="localized_route('news.index')" :link-label="__('home.news.all')" />
            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,300px),1fr))] gap-6">
                @foreach ($related as $item)
                    <x-card.news :tag="$item['tag']" :date="$item['date']" :title="$item['title']" :url="$item['url']" :image="$item['image']" />
                @endforeach
            </div>
        </x-section>
    @endif
</x-layout.app>
