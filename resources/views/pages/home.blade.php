<x-layout.app :seo="['page' => 'home', 'schema' => $schema]">
    <x-hero :image="$heroImage" :categories="$categories" />

    @foreach ($sections as $section)
        @include('pages.home.'.$section, ['alt' => $loop->odd])
    @endforeach

    <x-cta-banner id="post" :href="localized_route('listings.create')" :title="__('home.cta.title')" :text="__('home.cta.text')" :action="__('home.cta.button')" />
</x-layout.app>
