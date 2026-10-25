<x-layout.app :title="__('site.classifieds.title')" :description="__('site.classifieds.subtitle')">
    <x-page-header :title="__('site.classifieds.title')" :subtitle="__('site.classifieds.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.classifieds')]]" />
    <livewire:listings.classified-index />
</x-layout.app>
