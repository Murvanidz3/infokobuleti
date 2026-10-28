<x-layout.app :title="__('site.jobs.title')" :description="__('site.jobs.subtitle')" :seo="['page' => 'jobs']">
    <x-page-header :title="__('site.jobs.title')" :subtitle="__('site.jobs.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.jobs')]]" />
    <livewire:listings.vacancy-index />
</x-layout.app>
