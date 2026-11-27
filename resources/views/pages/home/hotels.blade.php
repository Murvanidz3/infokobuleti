<x-section :background="$alt ? 'alt' : 'white'" id="hotels" aria-labelledby="hotels-title">
    <div x-data="carousel">
        <x-section-header id="hotels-title" :title="__('home.hotels.title')" :subtitle="__('home.hotels.subtitle')" :href="daily_rent_hotels_url()" :link-label="__('home.hotels.all')">
            <x-slot:actions>
                <x-carousel.arrows />
            </x-slot:actions>
        </x-section-header>
        <x-carousel :label="__('home.hotels.title')" x-ignore>
            @foreach ($hotels as $hotel)
                <x-carousel.item>
                    <x-card.property class="h-full" :badge="$hotel['badge']" :price="$hotel['price']" :converted="$hotel['converted']" :title="$hotel['title']" :location="$hotel['location']" :features="$hotel['features']" :url="$hotel['url']" :image="$hotel['image']" :saved="$hotel['saved']" :favorite="$hotel['favorite']" :eager="$loop->first" :promotion="$hotel['promotion'] ?? null" :logo="$hotel['logo'] ?? null" :owner-verified="$hotel['ownerVerified'] ?? false" />
                </x-carousel.item>
            @endforeach
        </x-carousel>
    </div>
</x-section>
