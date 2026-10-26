<x-layout.app :seo="['page' => 'home', 'schema' => $schema]">
    <x-hero :image="$heroImage" :search-options="$searchOptions" />

    <x-section spacing="compact" aria-labelledby="categories-title">
        <h2 id="categories-title" class="sr-only">{{ __('home.categories.title') }}</h2>
        <ul class="grid grid-cols-2 gap-3.5 xs:grid-cols-4 xl:grid-cols-8">
            @foreach ($categories as $category)
                <li><x-category-tile :name="$category['name']" :count="$category['count']" :icon="$category['icon']" :url="$category['url']" /></li>
            @endforeach
        </ul>
    </x-section>

    @foreach ($sections as $section)
        @include('pages.home.'.$section, ['alt' => $loop->odd])
    @endforeach

    <x-cta-banner id="post" :href="localized_route('listings.create')" :title="__('home.cta.title')" :text="__('home.cta.text')" :action="__('home.cta.button')" />
</x-layout.app>
