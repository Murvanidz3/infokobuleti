<x-layout.app :title="__('site.real_estate.title')" :description="__('site.real_estate.subtitle')" :seo="['page' => 'real_estate']">
    <x-page-header :title="__('site.real_estate.title')" :subtitle="__('site.real_estate.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.real_estate')]]" />
    <livewire:listings.property-index />
</x-layout.app>
