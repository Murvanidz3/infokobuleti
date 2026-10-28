<x-section :background="$alt ? 'alt' : 'white'" id="real-estate" aria-labelledby="real-estate-title">
    <div x-data="segmented('all')">
        <x-section-header id="real-estate-title" :title="__('home.real_estate.title')" :subtitle="__('home.real_estate.subtitle')">
            <x-slot:actions>
                <x-segmented :options="__('home.real_estate.filters')" :label="__('home.real_estate.filter_label')" selected="all" />
            </x-slot:actions>
        </x-section-header>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,340px),1fr))] gap-6">
            @foreach ($properties as $property)
                <x-card.property :data-segment="$property['deal']" x-ignore :badge="$property['badge']" :price="$property['price']" :converted="$property['converted']" :title="$property['title']" :location="$property['location']" :features="$property['features']" :url="$property['url']" :image="$property['image']" :saved="$property['saved']" :favorite="$property['favorite']" :promotion="$property['promotion'] ?? null" :logo="$property['logo'] ?? null" :owner-verified="$property['ownerVerified'] ?? false" />
            @endforeach
        </div>
    </div>
</x-section>
