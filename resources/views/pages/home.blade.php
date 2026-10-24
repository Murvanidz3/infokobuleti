<x-layout.app>
    <x-hero />

    <x-section spacing="compact" aria-labelledby="categories-title">
        <h2 id="categories-title" class="sr-only">{{ __('home.categories.title') }}</h2>
        <ul class="grid grid-cols-2 gap-3.5 xs:grid-cols-4 xl:grid-cols-8">
            @foreach ($categories as $category)
                <li><x-category-tile :name="$category['name']" :count="$category['count']" :icon="$category['icon']" :url="$category['url']" /></li>
            @endforeach
        </ul>
    </x-section>

    <x-section id="hotels" background="alt" aria-labelledby="hotels-title">
        <div x-data="carousel">
            <x-section-header id="hotels-title" :title="__('home.hotels.title')" :subtitle="__('home.hotels.subtitle')" href="#" :link-label="__('home.hotels.all')">
                <x-slot:actions>
                    <x-carousel.arrows />
                </x-slot:actions>
            </x-section-header>
            <x-carousel :label="__('home.hotels.title')" x-ignore>
                @foreach ($hotels as $hotel)
                    <x-carousel.item>
                        <x-card.hotel class="h-full" :name="$hotel['name']" :address="$hotel['address']" :sea="$hotel['sea']" :rating="$hotel['rating']" :reviews="$hotel['reviews']" :price="$hotel['price']" :url="$hotel['url']" :image="$hotel['image']" :saved="$hotel['saved']" />
                    </x-carousel.item>
                @endforeach
            </x-carousel>
        </div>
    </x-section>

    <x-section id="real-estate" aria-labelledby="real-estate-title">
        <div x-data="segmented('all')">
            <x-section-header id="real-estate-title" :title="__('home.real_estate.title')" :subtitle="__('home.real_estate.subtitle')">
                <x-slot:actions>
                    <x-segmented :options="__('home.real_estate.filters')" :label="__('home.real_estate.filter_label')" selected="all" />
                </x-slot:actions>
            </x-section-header>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,340px),1fr))] gap-6">
                @foreach ($properties as $property)
                    <x-card.property :data-segment="$property['deal']" x-ignore :badge="$property['badge']" :price="$property['price']" :converted="$property['converted']" :title="$property['title']" :location="$property['location']" :features="$property['features']" :url="$property['url']" :image="$property['image']" :saved="$property['saved']" />
                @endforeach
            </div>
        </div>
    </x-section>

    <x-section id="classifieds" background="alt">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,460px),1fr))] gap-x-8 gap-y-[clamp(40px,3vw,48px)]">
            <div aria-labelledby="classifieds-title" role="region">
                <x-section-header size="sm" id="classifieds-title" :title="__('home.classifieds.title')" href="#" />
                <x-list-panel>
                    @foreach ($classifieds as $classified)
                        <x-card.classified :title="$classified['title']" :meta="$classified['meta']" :price="$classified['price']" :time="$classified['time']" :icon="$classified['icon']" :is-new="$classified['isNew']" :url="$classified['url']" />
                    @endforeach
                </x-list-panel>
            </div>
            <div id="jobs" aria-labelledby="jobs-title" role="region">
                <x-section-header size="sm" id="jobs-title" :title="__('home.jobs.title')" href="#" />
                <x-list-panel>
                    @foreach ($jobs as $job)
                        <x-card.job :title="$job['title']" :employer="$job['employer']" :salary="$job['salary']" :period="$job['period']" :type="$job['type']" :seasonal="$job['seasonal']" :posted="$job['posted']" :url="$job['url']" />
                    @endforeach
                </x-list-panel>
            </div>
        </div>
    </x-section>

    <x-section id="guide" aria-labelledby="guide-title">
        <x-section-header id="guide-title" :title="__('home.guide.title')" :subtitle="__('home.guide.subtitle')" href="#" :link-label="__('home.guide.all')" />
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

    <x-section background="alt" spacing="tight" class="border-y border-line" aria-labelledby="info-title">
        <div class="mb-5 flex flex-wrap items-baseline justify-between gap-4">
            <h2 id="info-title" class="font-serif text-[22px] font-semibold text-navy">{{ __('home.info.title') }}</h2>
            <span class="text-[13.5px] text-secondary">{{ $info['updated'] }}</span>
        </div>
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,250px),1fr))] gap-4">
            <x-info-widget icon="sun" :label="__('home.info.weather')">
                <div class="flex items-end gap-5">
                    <x-info-widget.stat size="xl" :value="$info['weather']['air']" :caption="$info['weather']['condition']" />
                    <x-info-widget.stat size="xl" tone="sea" class="border-l border-line pl-5" :value="$info['weather']['sea']" :caption="__('home.info.sea_water')" />
                </div>
            </x-info-widget>
            <x-info-widget icon="exchange" :label="__('home.info.rates')">
                <dl class="grid grid-cols-3 gap-2">
                    @foreach ($info['rates'] as $currency => $rate)
                        <div>
                            <dt class="text-xs font-semibold text-secondary">{{ $currency }}</dt>
                            <dd class="mt-1 text-[19px] font-semibold text-navy">{{ $rate }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-info-widget>
            <x-info-widget icon="plane" :label="__('home.info.airport')">
                <x-info-widget.stat :value="$info['airport']['distance']" :caption="$info['airport']['note']" />
            </x-info-widget>
            <x-info-widget icon="train" :label="__('home.info.railway')">
                <x-info-widget.stat :value="$info['railway']['value']" :caption="$info['railway']['note']" />
            </x-info-widget>
        </div>
    </x-section>

    <x-section id="news" aria-labelledby="news-title">
        <x-section-header id="news-title" :title="__('home.news.title')" :subtitle="__('home.news.subtitle')" href="#" :link-label="__('home.news.all')" />
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,320px),1fr))] gap-6">
            @foreach ($news as $item)
                <x-card.news :tag="$item['tag']" :date="$item['date']" :title="$item['title']" :url="$item['url']" :image="$item['image']" />
            @endforeach
        </div>
    </x-section>

    <x-cta-banner id="post" :title="__('home.cta.title')" :text="__('home.cta.text')" :action="__('home.cta.button')" />
</x-layout.app>
