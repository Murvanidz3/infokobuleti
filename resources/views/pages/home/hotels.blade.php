<x-section :background="$alt ? 'alt' : 'white'" id="hotels" aria-labelledby="hotels-title">
    <div x-data="carousel">
        <x-section-header id="hotels-title" :title="__('home.hotels.title')" :subtitle="__('home.hotels.subtitle')" :href="localized_route('hotels.index')" :link-label="__('home.hotels.all')">
            <x-slot:actions>
                <x-carousel.arrows />
            </x-slot:actions>
        </x-section-header>
        <x-carousel :label="__('home.hotels.title')" x-ignore>
            @foreach ($hotels as $hotel)
                <x-carousel.item>
                    <x-card.hotel class="h-full" :name="$hotel['name']" :address="$hotel['address']" :sea="$hotel['sea']" :rating="$hotel['rating']" :reviews="$hotel['reviews']" :price="$hotel['price']" :url="$hotel['url']" :image="$hotel['image']" :saved="$hotel['saved']" :favorite="$hotel['favorite']" :eager="$loop->first" :promotion="$hotel['promotion'] ?? null" :logo="$hotel['logo'] ?? null" />
                </x-carousel.item>
            @endforeach
        </x-carousel>
    </div>
</x-section>
