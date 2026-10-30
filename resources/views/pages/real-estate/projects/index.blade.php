<x-layout.app :title="$title" :description="$subtitle" :seo="['page' => 'projects']">
    <x-page-header :title="$title" :subtitle="$subtitle"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.real_estate'), 'url' => localized_route('real-estate.index')], ['label' => __('nav.projects')]]" />
    <livewire:listings.project-index />
</x-layout.app>
