<x-layout.app :title="__('site.hotels.title')" :description="__('site.hotels.subtitle')">
    <x-page-header :title="__('site.hotels.title')" :subtitle="__('site.hotels.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.hotels')]]" />
    <livewire:listings.hotel-index />
</x-layout.app>
