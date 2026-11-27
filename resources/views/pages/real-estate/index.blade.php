@php($vacation = viewing_vacation_index())
<x-layout.app :title="__($vacation ? 'site.vacation.title' : 'site.real_estate.title')" :description="__($vacation ? 'site.vacation.subtitle' : 'site.real_estate.subtitle')" :seo="['page' => $vacation ? 'vacation' : 'real_estate']">
    <x-page-header :title="__($vacation ? 'site.vacation.title' : 'site.real_estate.title')" :subtitle="__($vacation ? 'site.vacation.subtitle' : 'site.real_estate.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __($vacation ? 'nav.hotels' : 'nav.real_estate')]]" />
    <livewire:listings.property-index />
</x-layout.app>
